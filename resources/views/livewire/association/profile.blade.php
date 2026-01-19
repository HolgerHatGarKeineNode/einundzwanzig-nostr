<?php

use App\Enums\AssociationStatus;
use App\Livewire\Forms\ApplicationForm;
use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Flux\Flux;
use Livewire\Component;
use swentel\nostr\Event\Event as NostrEvent;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Sign\Sign;
use swentel\nostr\Subscription\Subscription;

new class extends Component
{
    public ApplicationForm $form;

    public bool $no = false;

    public bool $showEmail = true;

    public string $fax = '';

    public ?string $email = '';

    public array $yearsPaid = [];

    public array $events = [];

    public $payments;

    public int $amountToPay;

    public bool $currentYearIsPaid = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    public ?string $qrCode = null;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
    ];

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = EinundzwanzigPleb::query()
                ->with([
                    'paymentEvents' => fn ($query) => $query->where('year', date('Y')),
                ])
                ->where('pubkey', $this->currentPubkey)->first();
            if ($this->currentPleb) {
                $this->email = $this->currentPleb->email;
                $this->showEmail = ! $this->no;
                if ($this->currentPleb->association_status === AssociationStatus::ACTIVE) {
                    $this->amountToPay = config('app.env') === 'production' ? 21000 : 1;
                }
                if ($this->currentPleb->paymentEvents->count() < 1) {
                    $this->createPaymentEvent();
                    $this->currentPleb->load('paymentEvents');
                }
                $this->loadEvents();
                $this->listenForPayment();
            }
        }
    }

    public function updatedNo(): void
    {
        $this->showEmail = ! $this->no;
        $this->currentPleb->update([
            'no_email' => $this->no,
        ]);
    }

    public function updatedFax(): void
    {
        $this->js('alert("Markus Turm wird sich per Fax melden!")');
    }

    public function saveEmail(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);
        $this->currentPleb->update([
            'email' => $this->email,
        ]);
        Flux::toast('E-Mail Adresse gespeichert.');
    }

    public function pay($comment): mixed
    {
        $paymentEvent = $this->currentPleb
            ->paymentEvents()
            ->where('year', date('Y'))
            ->first();
        if ($paymentEvent->btc_pay_invoice) {
            return redirect()->away('https://pay.einundzwanzig.space/i/'.$paymentEvent->btc_pay_invoice);
        }
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'token '.config('services.btc_pay.api_key'),
            ])->post(
                'https://pay.einundzwanzig.space/api/v1/stores/98PF86BoMd3C8P1nHHyFdoeznCwtcm5yehcAgoCYDQ2a/invoices',
                [
                    'amount' => $this->amountToPay,
                    'metadata' => [
                        'orderId' => $comment,
                        'orderUrl' => url()->route('association.profile'),
                        'itemDesc' => 'Mitgliedsbeitrag '.date('Y').' von nostr:'.$this->currentPleb->npub,
                        'posData' => [
                            'event' => $paymentEvent->event_id,
                            'pubkey' => $this->currentPleb->pubkey,
                            'npub' => $this->currentPleb->npub,
                        ],
                    ],
                    'checkout' => [
                        'expirationMinutes' => 60 * 24,
                        'redirectURL' => url()->route('association.profile'),
                        'redirectAutomatically' => true,
                        'defaultLanguage' => 'de',
                    ],
                ],
            )->throw();
            $paymentEvent->btc_pay_invoice = $response->json()['id'];
            $paymentEvent->save();

            return redirect()->away($response->json()['checkoutLink']);
        } catch (\Exception $e) {
            Flux::toast(
                'Fehler beim Erstellen der Rechnung. Bitte versuche es später erneut: '.$e->getMessage(),
                variant: 'danger',
            );

            return redirect()->route('association.profile');
        }
    }

    public function listenForPayment(): void
    {
        $paymentEvent = $this->currentPleb
            ->paymentEvents()
            ->where('year', date('Y'))
            ->first();
        if ($paymentEvent->btc_pay_invoice) {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'token '.config('services.btc_pay.api_key'),
            ])
                ->get(
                    'https://pay.einundzwanzig.space/api/v1/stores/98PF86BoMd3C8P1nHHyFdoeznCwtcm5yehcAgoCYDQ2a/invoices/'.$paymentEvent->btc_pay_invoice,
                );
            if ($response->json()['status'] === 'Expired') {
                $paymentEvent->btc_pay_invoice = null;
                $paymentEvent->paid = false;
                $paymentEvent->save();
            }
            if ($response->json()['status'] === 'Settled') {
                $paymentEvent->paid = true;
                $paymentEvent->save();
                $this->currentYearIsPaid = true;
            }
        }
        if ($paymentEvent->paid) {
            $this->currentYearIsPaid = true;
        }
        $paymentEvent = $paymentEvent->refresh();
        $this->payments = $this->currentPleb
            ->paymentEvents()
            ->where('paid', true)
            ->get();
    }

    public function save($type): void
    {
        $this->form->validate();
        if (! $this->form->check) {
            $this->js('alert("Du musst den Statuten zustimmen.")');

            return;
        }

        $this->currentPleb
            ->update([
                'association_status' => $type,
            ]);
    }

    public function createPaymentEvent(): void
    {
        $note = new NostrEvent;
        $note->setKind(32121);
        $note->setContent(
            'Dieses Event dient der Zahlung des Mitgliedsbeitrags für das Jahr '.date(
                'Y',
            ).'. Bitte bezahle den Betrag von '.number_format($this->amountToPay, 0, ',', '.').' Satoshis.',
        );
        $note->setTags([
            ['d', $this->currentPleb->pubkey.','.date('Y')],
            ['zap', 'daf83d92768b5d0005373f83e30d4203c0b747c170449e02fea611a0da125ee6', config('services.relay'), '1'],
        ]);
        $signer = new Sign;
        $signer->signEvent($note, config('services.nostr'));

        $eventMessage = new EventMessage($note);

        $relayUrl = config('services.relay');
        $relay = new Relay($relayUrl);
        $relay->setMessage($eventMessage);
        $result = $relay->send();

        $this->currentPleb->paymentEvents()->create([
            'year' => date('Y'),
            'event_id' => $result->eventId,
            'amount' => $this->amountToPay,
        ]);
    }

    public function loadEvents(): void
    {
        $subscription = new Subscription;
        $subscriptionId = $subscription->setId();

        $filter1 = new Filter;
        $filter1->setKinds([32121]);
        $filter1->setAuthors(['daf83d92768b5d0005373f83e30d4203c0b747c170449e02fea611a0da125ee6']);
        $filters = [$filter1];

        $requestMessage = new RequestMessage($subscriptionId, $filters);

        $relays = [
            new Relay(config('services.relay')),
        ];
        $relaySet = new RelaySet;
        $relaySet->setRelays($relays);

        $request = new Request($relaySet, $requestMessage);
        $response = $request->send();

        $this->events = collect($response[config('services.relay')])
            ->map(function ($event) {
                if (! isset($event->event)) {
                    return false;
                }

                return [
                    'id' => $event->event->id,
                    'kind' => $event->event->kind,
                    'content' => $event->event->content,
                    'pubkey' => $event->event->pubkey,
                    'tags' => $event->event->tags,
                    'created_at' => $event->event->created_at,
                ];
            })
            ->filter()
            ->unique('id')
            ->toArray();
    }
}
?>

<div>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl text-[#1B1B1B] dark:text-gray-100 font-bold">
                Einundzwanzig ist, was du draus machst
            </h1>
        </div>

        <!-- Main Grid Layout -->
        <div class="grid grid-cols-1 gap-6">
            <!-- Status Section -->
            <flux:card>
                <h2 class="text-xl md:text-2xl text-[#1B1B1B] dark:text-gray-100 font-bold mb-6">
                    Aktueller Status
                </h2>

                @if(!$currentPleb)
                    <!-- Nostr Login Apps Section -->
                    <div class="space-y-4 mb-8">
                        <h3 class="text-lg md:text-xl text-gray-500 dark:text-gray-400 italic mb-4">
                            Empfohlene Nostr Login und Signer-Apps
                        </h3>

                        <!-- Grid of App Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:card>
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex-1">
                                        <a class="font-semibold text-gray-800 dark:text-gray-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                                           href="https://github.com/greenart7c3/Amber">
                                            Amber
                                        </a>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Perfekt für mobile Android Geräte. Eine App, in der man alle Keys/nsecs verwalten kann.
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <flux:badge color="success">Android</flux:badge>
                                    </div>
                                </div>
                            </flux:card>

                            <flux:card>
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex-1">
                                        <a class="font-semibold text-gray-800 dark:text-gray-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                                           href="https://addons.mozilla.org/en-US/firefox/addon/alby/">
                                            Alby - Bitcoin Lightning Wallet & Nostr
                                        </a>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Browser-Erweiterung in die man seinen Key/nsec eingeben kann. Pro Alby-Konto ein nsec.
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <flux:badge color="warning">Browser Chrome/Firefox</flux:badge>
                                    </div>
                                </div>
                            </flux:card>

                            <flux:card>
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex-1">
                                        <a class="font-semibold text-gray-800 dark:text-gray-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                                           href="https://chromewebstore.google.com/detail/nos2x/kpgefcfmnafjgpblomihpgmejjdanjjp">
                                            nos2x
                                        </a>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Browser-Erweiterung für Chrome Browser. Multi-Key fähig.
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <flux:badge color="danger">Browser Chrome</flux:badge>
                                    </div>
                                </div>
                            </flux:card>

                            <flux:card>
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex-1">
                                        <a class="font-semibold text-gray-800 dark:text-gray-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                                           href="https://addons.mozilla.org/en-US/firefox/addon/nos2x-fox/">
                                            nos2x-fox
                                        </a>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Browser-Erweiterung für Firefox Browser. Multi-Key fähig.
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <flux:badge color="amber">Browser Firefox</flux:badge>
                                    </div>
                                </div>
                            </flux:card>
                        </div>

                        <!-- User Profile Display -->
                        <div class="mt-6">
                            <template x-if="$store.nostr.user">
                                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <img class="w-12 h-12 rounded-full"
                                         x-bind:src="$store.nostr.user.picture || '{{ asset('apple-touch-icon.png') }}'"
                                         alt="Avatar">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="truncate text-lg leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold"
                                            x-text="$store.nostr.user.display_name"></h3>
                                        <div class="truncate text-sm text-gray-500 dark:text-gray-400"
                                             x-text="$store.nostr.user.name"></div>
                                    </div>
                                </div>
                            </template>

                            @if($currentPubkey && $currentPleb->association_status->value < 2)
                                <flux:card class="mt-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="shrink-0 fill-current text-green-500 mt-0.5" width="16" height="16" viewBox="0 0 16 16">
                                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM7 11.4L3.6 8 5 6.6l2 2 4-4L12.4 6 7 11.4z"></path>
                                        </svg>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">Profil in der Datenbank vorhanden.</p>
                                    </div>
                                </flux:card>
                            @endif
                        </div>
                    </div>
                @endif

                @if($currentPubkey && !$currentPleb->application_for && $currentPleb->association_status->value < 2)
                    <!-- Membership Registration Section -->
                    <div class="space-y-4 py-6 border-t border-gray-200 dark:border-gray-600">
                        <div>
                            <h3 class="text-xl md:text-2xl text-[#1B1B1B] dark:text-gray-100 font-bold mb-2">
                                Einundzwanzig Mitglied werden
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Nur Personen können Mitglied werden und zahlen 21.000 Satoshis im Jahr.
                                <a href="https://einundzwanzig.space/verein/" class="text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 font-medium">
                                    Firmen melden sich bitte direkt an den Vorstand.
                                </a>
                            </p>
                        </div>

                        <div class="flex flex-col gap-4 max-w-2xl">
                            <flux:field variant="inline">
                                <flux:checkbox wire:model="form.check" label="Ich stimme den Vereins-Statuten zu"/>
                                <flux:error name="form.check"/>
                            </flux:field>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <flux:button wire:click="save({{ AssociationStatus::PASSIVE() }})" variant="primary">
                                    Mit deinem aktuellen Nostr-Profil Mitglied werden
                                </flux:button>
                                <flux:button href="https://einundzwanzig.space/verein/" target="_blank" variant="outline">
                                    Statuten ansehen
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($currentPubkey)
                    <!-- Email Settings Section -->
                    <div class="py-6 border-t border-gray-200 dark:border-gray-600">
                        <flux:callout variant="warning" class="mb-6">
                            <div class="space-y-4">
                                <p class="font-medium text-gray-800 dark:text-gray-100">
                                    Falls du möchtest, kannst du hier eine E-Mail Adresse hinterlegen, damit der Verein dich darüber informieren kann, wenn es Neuigkeiten gibt.
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Am besten eine anonymisierte E-Mail Adresse verwenden. Wir sichern diese Adresse AES-256 verschlüsselt in der Datenbank ab.
                                </p>
                            </div>
                        </flux:callout>

                        <div class="space-y-4 max-w-2xl">
                            <flux:field variant="inline">
                                <flux:checkbox
                                    wire:model.live="no"
                                    label="Keine E-Mail Adresse angeben"
                                    description="Ich informiere mich selbst in der News Sektion und gebe keine E-Mail Adresse raus."/>
                                <flux:error name="no"/>
                            </flux:field>

                            @if(!$no)
                                <div wire:key="showEmail" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Fax-Nummer</flux:label>
                                        <flux:input wire:model.live.debounce="fax" placeholder="Fax-Nummer"/>
                                        <flux:error name="fax"/>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>E-Mail Adresse</flux:label>
                                        <flux:input type="email" wire:model.live.debounce="email" placeholder="E-Mail Adresse"/>
                                        <flux:error name="email"/>
                                    </flux:field>
                                </div>

                                <div wire:key="showSave">
                                    <flux:button wire:click="saveEmail" wire:loading.attr="disabled">
                                        Speichern
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Active Member Status -->
            @if($currentPleb && $currentPleb->association_status->value > 1)
                <flux:card>
                    <div class="flex items-start gap-3">
                        <svg class="shrink-0 fill-current text-green-500 mt-0.5" width="16" height="16" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-100">
                                @if($currentYearIsPaid)
                                    <span class="text-green-600 dark:text-green-400">Du bist derzeit ein Mitglied des Vereins. Das aktuelle Jahr ist bezahlt.</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">Du wirst nach Zahlung des Vereinsbeitrages zum Mitglied. Das aktuelle Jahr ist noch nicht bezahlt.</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </flux:card>
            @endif

            <!-- Payment Section -->
            @if($currentPleb && $currentPleb->association_status->value > 1)
                <flux:card>
                    <div class="space-y-6">
                        <!-- Payment Info -->
                        <div>
                            <h3 class="text-xl md:text-2xl text-gray-800 dark:text-gray-100 font-bold mb-4">
                                Mitgliedsbeitrag
                            </h3>

                            <flux:callout variant="info" class="mb-6">
                                <p class="text-sm">
                                    Nostr Event für die Zahlung des Mitgliedsbeitrags:
                                    <span class="block mt-2 font-mono text-xs break-all">{{ $currentPleb->paymentEvents->last()->event_id }}</span>
                                </p>
                            </flux:callout>

                            @php
                                $latestEvent = collect($events)->sortByDesc('created_at')->first();
                            @endphp

                            @if(isset($latestEvent))
                                <p class="text-gray-700 dark:text-gray-300 mb-6">{{ $latestEvent['content'] }}</p>

                                <!-- Payment Button -->
                                <div class="flex justify-center py-6">
                                    @if(!$currentYearIsPaid)
                                        <flux:button
                                            wire:click="pay('{{ date('Y') }}:{{ $currentPubkey }}')"
                                            variant="primary"
                                            class="text-xl px-8 py-3">
                                            <i class="fa-sharp-duotone fa-solid fa-bolt-lightning mr-2"></i>
                                            Pay {{ $amountToPay }} Sats
                                        </flux:button>
                                    @else
                                        <flux:button disabled variant="primary" color="green" class="text-xl px-8 py-3">
                                            <i class="fa-sharp-duotone fa-solid fa-check-circle mr-2"></i>
                                            Aktuelles Jahr bezahlt
                                        </flux:button>
                                    @endif
                                </div>
                            @else
                                <flux:callout variant="danger">
                                    <div class="flex items-start gap-3">
                                        <i class="fa-sharp-duotone fa-solid fa-user-helmet-safety mt-1"></i>
                                        <p class="text-sm">
                                            Unser Nostr-Relay konnte derzeit nicht erreicht werden, um eine Zahlung zu initialisieren. Bitte versuche es später noch einmal.
                                        </p>
                                    </div>
                                </flux:callout>
                            @endif
                        </div>

                        <!-- Payment History -->
                        @if($payments && count($payments) > 0)
                            <div class="pt-6 border-t border-gray-200 dark:border-gray-600">
                                <h4 class="text-lg md:text-xl text-gray-800 dark:text-gray-100 font-bold mb-4">
                                    Bisherige Zahlungen
                                </h4>

                                <!-- Desktop Table -->
                                <div class="hidden md:block overflow-x-auto">
                                    <table class="table-auto w-full">
                                        <thead class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-600">
                                            <tr>
                                                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                                    <div class="font-semibold">Satoshis</div>
                                                </th>
                                                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                                    <div class="font-semibold">Jahr</div>
                                                </th>
                                                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                                    <div class="font-semibold">Event-ID</div>
                                                </th>
                                                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                                                    <div class="font-semibold">Quittung</div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-600">
                                            @foreach($payments as $payment)
                                                <tr>
                                                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $payment->amount }}</div>
                                                    </td>
                                                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                                        <div class="text-gray-800 dark:text-gray-100">{{ $payment->year }}</div>
                                                    </td>
                                                    <td class="px-2 first:pl-5 last:pr-5 py-3">
                                                        <div class="font-mono text-xs text-gray-600 dark:text-gray-400 break-all">{{ $payment->event_id }}</div>
                                                    </td>
                                                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                                        @if($payment->btc_pay_invoice)
                                                            <flux:button
                                                                href="https://pay.einundzwanzig.space/i/{{ $payment->btc_pay_invoice }}/receipt"
                                                                target="_blank"
                                                                size="xs"
                                                                variant="subtle">
                                                                Quittung
                                                            </flux:button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile Cards -->
                                <div class="md:hidden space-y-4">
                                    @foreach($payments as $payment)
                                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 p-4">
                                            <div class="space-y-3">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Satoshis</span>
                                                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $payment->amount }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Jahr</span>
                                                    <span class="text-gray-800 dark:text-gray-100">{{ $payment->year }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-1">Event-ID</span>
                                                    <span class="font-mono text-xs text-gray-600 dark:text-gray-400 break-all">{{ $payment->event_id }}</span>
                                                </div>
                                                @if($payment->btc_pay_invoice)
                                                    <flux:button
                                                        href="https://pay.einundzwanzig.space/i/{{ $payment->btc_pay_invoice }}/receipt"
                                                        target="_blank"
                                                        variant="subtle"
                                                        class="w-full text-sm">
                                                        Quittung anzeigen
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>
