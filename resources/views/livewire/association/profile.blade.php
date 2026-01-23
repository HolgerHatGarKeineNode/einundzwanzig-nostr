<?php

use App\Livewire\Forms\ApplicationForm;
use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use App\Traits\NostrFetcherTrait;
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
    use NostrFetcherTrait;

    public ApplicationForm $form;

    public bool $no = false;

    public bool $showEmail = true;

    public string $fax = '';

    public ?string $email = '';

    public ?string $nip05Handle = '';

    public bool $nip05Verified = false;

    public ?string $nip05VerifiedHandle = null;

    public bool $nip05HandleMismatch = false;

    public array $nip05VerifiedHandles = [];

    public array $yearsPaid = [];

    public array $events = [];

    public $payments;

    public int $amountToPay = 21000;

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
                    'profile',
                ])
                ->where('pubkey', $this->currentPubkey)->first();
            if ($this->currentPleb) {
                $this->email = $this->currentPleb->email;
                if ($this->currentPleb->nip05_handle) {
                    $this->nip05Handle = $this->currentPleb->nip05_handle;

                    // Get all NIP-05 handles for the current pubkey
                    $this->nip05VerifiedHandles = $this->getNip05HandlesForPubkey($this->currentPubkey);

                    if (count($this->nip05VerifiedHandles) > 0) {
                        $this->nip05Verified = true;
                        $this->nip05VerifiedHandle = $this->nip05VerifiedHandles[0];

                        // Check if verified handle differs from database handle
                        if (! in_array($this->nip05Handle, $this->nip05VerifiedHandles, true)) {
                            $this->nip05HandleMismatch = true;
                        }
                    }
                }
                $this->no = $this->currentPleb->no_email;
                $this->showEmail = ! $this->no;
                $this->amountToPay = config('app.env') === 'production' ? 21000 : 1;
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

    public function updatedNip05Handle(): void
    {
        $this->nip05Handle = strtolower($this->nip05Handle);
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

    public function saveNip05Handle(): void
    {
        $this->validate([
            'nip05Handle' => 'required|string|max:255|regex:/^[a-z0-9_-]+$/|unique:einundzwanzig_plebs,nip05_handle',
        ]);

        $nip05Handle = strtolower($this->nip05Handle);

        $this->currentPleb->update([
            'nip05_handle' => $nip05Handle,
        ]);
        Flux::toast('NIP-05 Handle gespeichert.');
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

    public function copyRelayUrl(): void
    {
        $relayUrl = 'wss://nostr.einundzwanzig.space';
        $this->js("navigator.clipboard.writeText('{$relayUrl}')");
        Flux::toast('Relay-Adresse in die Zwischenablage kopiert!');
    }

    public function copyWatchtowerUrl(): void
    {
        $watchtowerUrl = '03a09f56bba3d2c200cc55eda2f1f069564a97c1fb74345e1560e2868a8ab3d7d0@62.171.139.240:9911';
        $this->js("navigator.clipboard.writeText('{$watchtowerUrl}')");
        Flux::toast('Watchtower-Adresse in die Zwischenablage kopiert!');
    }
}
?>

<div>
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl text-[#1B1B1B] dark:text-zinc-100 font-bold">
            Einundzwanzig ist, was du draus machst
        </h1>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <!-- Membership Benefits Section -->
        <flux:card class="w-full md:w-1/3">
            <div class="flex max-md:flex-col items-start">
                <div class="flex-1 max-md:pt-6 self-stretch">
                    <flux:heading size="xl" level="1">Vorteile deiner Mitgliedschaft</flux:heading>
                    <flux:separator variant="subtle" class="mb-6"/>

                    <!-- Benefits Grid -->
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Benefit 1 -->
                        <div
                            class="bg-linear-to-br from-amber-50 to-orange-50 dark:from-amber-300/10 dark:to-orange-900/10 rounded-lg p-4 border border-amber-200 dark:border-amber-200/30">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0">
                                    <div
                                        class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/60 flex items-center justify-center">
                                        <i class="fa-sharp-duotone fa-solid fa-bolt text-amber-600 dark:text-amber-400 text-base"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-1">
                                        Nostr Relay
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        Exklusive Schreib-Rechte auf Premium Nostr Relay von Einundzwanzig.
                                    </p>
                                    @if($currentPleb && $currentPleb->association_status->value > 1 && $currentYearIsPaid)
                                        <div class="mt-3 space-y-2">
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                                Ein Outbox-Relay ist wie ein Postbote für deine Nostr-Nachrichten. Es speichert und
                                                verteilt deine Posts. Um unser Relay nutzen zu können, musst du es in deinem
                                                Nostr-Client hinzufügen.
                                            </p>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                                Gehe in deinem Nostr-Client zu den Einstellungen (meistens "Settings" oder
                                                "Relays") und füge folgende Outbox-Relay-Adresse hinzu:
                                            </p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <code
                                                    class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded text-zinc-700 dark:text-zinc-300 font-mono cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                                                    wire:click="copyRelayUrl">
                                                    wss://nostr.einundzwanzig.space
                                                </code>
                                            </div>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                                <strong>Wichtige Hinweise:</strong> Du kannst deine Posts auf mehreren Relays gleichzeitig
                                                veröffentlichen. So stellst du sicher, dass deine Inhalte auch über unser Relay erreichbar sind.
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Benefit 2: NIP-05 -->
                        <div
                            class="bg-linear-to-br from-emerald-50 to-teal-50 dark:from-emerald-300/10 dark:to-teal-900/10 rounded-lg p-4 border border-emerald-200 dark:border-emerald-200/30">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="shrink-0">
                                    <div
                                        class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center">
                                        <i class="fa-sharp-duotone fa-solid fa-check-circle text-emerald-600 dark:text-emerald-400 text-base"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-1">
                                        Get NIP-05 verified
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        Verifiziere deine Identität mit einem menschenlesbaren Nostr-Namen.
                                    </p>
                                </div>
                            </div>

                            <!-- NIP-05 Input -->
                            @if($currentPleb && $currentPleb->association_status->value > 1 && $currentYearIsPaid)
                                <div class="space-y-3">
                                    <flux:field>
                                        <flux:label>Dein NIP-05 Handle</flux:label>
                                        <flux:input.group>
                                            <flux:input
                                                wire:model.live.debounce="nip05Handle"
                                                placeholder="dein-name"
                                            />
                                            <flux:input.group.suffix>@einundzwanzig.space</flux:input.group.suffix>
                                        </flux:input.group>
                                        <flux:error name="nip05Handle"/>
                                    </flux:field>

                                    <div class="flex gap-3">
                                        <flux:button
                                            wire:click="saveNip05Handle"
                                            wire:loading.attr="disabled"
                                            size="sm"
                                            variant="primary">
                                            Speichern
                                        </flux:button>
                                    </div>

                                    <!-- Rules Info -->
                                    <div
                                        class="mt-3 p-3 bg-white/50 dark:bg-zinc-800/50 rounded border border-zinc-200 dark:border-zinc-600">
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                            <strong>Regeln für dein Handle:</strong> Nur Kleinbuchstaben (a-z), Zahlen
                                            (0-9) und die Zeichen "-" und "_" sind erlaubt. Dein Handle wird automatisch
                                            kleingeschrieben.
                                        </p>
                                    </div>

                                    <!-- Explanation -->
                                    <div
                                        class="mt-4 p-3 bg-white/50 dark:bg-zinc-800/50 rounded border border-zinc-200 dark:border-zinc-600">
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                            <flux:link href="https://nostr.how/en/guides/get-verified#self-hosted"
                                                       target="_blank">NIP-05
                                            </flux:link>
                                            verifiziert deine Identität auf Nostr. Das Handle ist wie eine
                                            E-Mail-Adresse (z.B. name@einundzwanzig.space). Clients zeigen ein Häkchen
                                            für verifizierte Benutzer. Dies macht dein Profil einfacher zu teilen und
                                            vertrauenswürdiger.
                                        </p>
                                    </div>

                                    <!-- NIP-05 Verification Status -->
                                    @if($nip05Verified)
                                        <flux:callout variant="success" icon="check-circle" class="mt-4">
                                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                                Du hast {{ count($nip05VerifiedHandles) }} aktive Handles für deinen Pubkey!
                                            </p>
                                            @if($nip05HandleMismatch)
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                                    Die Synchronisation zu <strong class="break-all">{{ $nip05Handle }}@einundzwanzig.space</strong> wird automatisch im Hintergrund durchgeführt.
                                                </p>
                                            @endif
                                        </flux:callout>

                                        <!-- List of all active handles -->
                                        <div class="mt-4 p-4 bg-white/50 dark:bg-zinc-800/50 rounded border border-zinc-200 dark:border-zinc-600">
                                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                                Deine aktivierten Handles:
                                            </p>
                                            <ul class="space-y-2">
                                                @foreach($nip05VerifiedHandles as $handle)
                                                    <li class="flex items-center gap-2 text-sm">
                                                        <span class="break-all text-zinc-800 dark:text-zinc-200 font-mono">
                                                            {{ $handle }}@einundzwanzig.space
                                                        </span>
                                                        <flux:badge color="green" size="xs">OK</flux:badge>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @elseif($nip05Handle)
                                        <flux:callout variant="secondary" icon="information-circle" class="mt-4">
                                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                                Dein Handle <strong class="break-all">{{ $nip05Handle }}@einundzwanzig.space</strong> ist noch nicht aktiv.
                                            </p>
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                                Das Handle ist gespeichert, aber noch nicht in der NIP-05 Konfiguration veröffentlicht.
                                                Der Vorstand wird dies bald aktivieren.
                                            </p>
                                        </flux:callout>
                                    @endif
                                </div>
                            @else
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 italic">
                                    Aktiviere deine Mitgliedschaft, um NIP-05 zu verifizieren.
                                </div>
                            @endif
                        </div>

                        <!-- Benefit 3: Lightning Watchtower -->
                        <div
                            class="bg-linear-to-br from-purple-50 to-blue-50 dark:from-purple-300/10 dark:to-blue-900/10 rounded-lg p-4 border border-purple-200 dark:border-purple-200/30">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="shrink-0">
                                    <div
                                        class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/60 flex items-center justify-center">
                                        <i class="fa-sharp-duotone fa-solid fa-shield-halved text-purple-600 dark:text-purple-400 text-base"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-1">
                                        Lightning Watchtower
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        Nutze unseren Watchtower zum Schutz deiner Lightning Channel.
                                    </p>
                                </div>
                            </div>

                            @if($currentPleb && $currentPleb->association_status->value > 1 && $currentYearIsPaid)
                                <div class="space-y-3">
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                        Ein Watchtower überwacht deine Lightning Channel und schützt sie, falls deine Node
                                        offline ist. Wenn du die Zahlung von Channel-Closing-Transaktionen verpasst, kümmert sich
                                        der Watchtower darum und verhindert den Verlust deiner Sats.
                                    </p>

                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                        Um unseren Watchtower zu nutzen, füge folgende URI in deiner Lightning Node
                                        Konfiguration hinzu:
                                    </p>

                                    <div class="flex items-center gap-2">
                                        <code
                                            class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded text-zinc-700 dark:text-zinc-300 font-mono cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors break-all"
                                            wire:click="copyWatchtowerUrl">
                                            03a09f56bba3d2c200cc55eda2f1f069564a97c1fb74345e1560e2868a8ab3d7d0@62.171.139.240:9911
                                        </code>
                                    </div>

                                    <div
                                        class="mt-3 p-3 bg-white/50 dark:bg-zinc-800/50 rounded border border-zinc-200 dark:border-zinc-600">
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed font-medium mb-2">
                                            Einrichtung für gängige Lightning Clients:
                                        </p>
                                        <ul class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed space-y-1 list-disc list-inside">
                                            <li><strong>LND:</strong> <flux:link href="https://docs.lightning.engineering/lightning-network-tools/lnd/watchtower" target="_blank">https://docs.lightning.engineering/lightning-network-tools/lnd/watchtower</flux:link></li>
                                            <li><strong>Core Lightning:</strong> Nutze den <code class="bg-zinc-200 dark:bg-zinc-700 px-1 rounded">watchtower-client</code> Plugin mit der URI</li>
                                            <li><strong>Eclair:</strong> Füge die URI zu den Watchtower-Einstellungen in deiner eclair.conf hinzu</li>
                                        </ul>
                                    </div>

                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                        <strong>Wichtig:</strong> Der Watchtower überwacht deine Channel passiv. Er hat keinen Zugriff auf
                                        deine privaten Schlüssel oder dein Guthaben.
                                    </p>
                                </div>
                            @else
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 italic">
                                    Aktiviere deine Mitgliedschaft, um den Lightning Watchtower zu nutzen.
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </flux:card>
        <!-- Main Grid Layout -->
        <div class="w-full md:w-2/3 grid grid-cols-1 gap-6">

            @if($currentPleb)
                <!-- Logged-in User Info -->
                <flux:callout variant="info">
                    <div class="flex items-start gap-4">
                        <img
                            class="w-12 h-12 rounded-full shrink-0 border-2 border-zinc-200 dark:border-zinc-600"
                            src="{{ $currentPleb->profile?->picture ?? asset('apple-touch-icon.png') }}"
                            alt="Avatar"
                        >
                        <div class="flex-1 min-w-0">
                            <div class="mb-2">
                                <h4 class="font-semibold text-zinc-800 dark:text-zinc-100 text-base">
                                    {{ $currentPleb->profile?->display_name ?? $currentPleb->profile?->name ?? 'Unbekannt' }}
                                </h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    @if($currentPleb->profile?->name)
                                        {{ $currentPleb->profile->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="space-y-1 text-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Pubkey:</span>
                                    <code
                                        class="bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded text-zinc-700 dark:text-zinc-300 break-all font-mono">
                                        {{ $currentPleb->pubkey }}
                                    </code>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Npub:</span>
                                    <code
                                        class="bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded text-zinc-700 dark:text-zinc-300 break-all font-mono text-xs">
                                        {{ $currentPleb->npub }}
                                    </code>
                                </div>
                                @if($currentPleb->nip05_handle)
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                        <span class="text-zinc-500 dark:text-zinc-400 shrink-0">NIP-05:</span>
                                        <code
                                            class="bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded text-zinc-700 dark:text-zinc-300 break-all font-mono text-xs">
                                            {{ $currentPleb->nip05_handle }}
                                        </code>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </flux:callout>
            @endif

            <!-- Status Section -->
            <flux:card>
                @if(!$currentPleb)
                    <!-- Nostr Login Apps Section -->
                    <div class="space-y-4 mb-8">
                        <h3 class="text-lg md:text-xl text-zinc-500 dark:text-zinc-400 italic mb-4">
                            Empfohlene Nostr Login und Signer-Apps
                        </h3>

                        <!-- Grid of App Cards -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-4 gap-4">
                            <flux:card class="h-full">
                                <div class="flex flex-col h-full gap-3 p-1">
                                    <div class="flex-1 min-w-0">
                                        <a class="font-semibold text-zinc-800 dark:text-zinc-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors block"
                                           href="https://github.com/greenart7c3/Amber">
                                            Amber
                                        </a>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2 leading-relaxed">
                                            Perfekt für mobile Android Geräte. Eine App, in der man alle Keys/nsecs
                                            verwalten kann.
                                        </p>
                                    </div>
                                    <div class="flex items-start sm:items-center">
                                        <flux:badge color="green">Android</flux:badge>
                                    </div>
                                </div>
                            </flux:card>

                            <flux:card class="h-full">
                                <div class="flex flex-col h-full gap-3 p-1">
                                    <div class="flex-1 min-w-0">
                                        <a class="font-semibold text-zinc-800 dark:text-zinc-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors block"
                                           href="https://addons.mozilla.org/en-US/firefox/addon/alby/">
                                            Alby - Bitcoin Lightning Wallet & Nostr
                                        </a>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2 leading-relaxed">
                                            Browser-Erweiterung in die man seinen Key/nsec eingeben kann. Pro Alby-Konto
                                            ein nsec.
                                        </p>
                                    </div>
                                    <div class="flex items-start sm:items-center">
                                        <flux:badge color="green">Browser Chrome/Firefox</flux:badge>
                                    </div>
                                </div>
                            </flux:card>

                            <flux:card class="h-full">
                                <div class="flex flex-col h-full gap-3 p-1">
                                    <div class="flex-1 min-w-0">
                                        <a class="font-semibold text-zinc-800 dark:text-zinc-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors block"
                                           href="https://chromewebstore.google.com/detail/nos2x/kpgefcfmnafjgpblomihpgmejjdanjjp">
                                            nos2x
                                        </a>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2 leading-relaxed">
                                            Browser-Erweiterung für Chrome Browser. Multi-Key fähig.
                                        </p>
                                    </div>
                                    <div class="flex items-start sm:items-center">
                                        <flux:badge color="green">Browser Chrome</flux:badge>
                                    </div>
                                </div>
                            </flux:card>

                            <flux:card class="h-full">
                                <div class="flex flex-col h-full gap-3 p-1">
                                    <div class="flex-1 min-w-0">
                                        <a class="font-semibold text-zinc-800 dark:text-zinc-100 hover:text-amber-500 dark:hover:text-amber-400 transition-colors block"
                                           href="https://addons.mozilla.org/en-US/firefox/addon/nos2x-fox/">
                                            nos2x-fox
                                        </a>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2 leading-relaxed">
                                            Browser-Erweiterung für Firefox Browser. Multi-Key fähig.
                                        </p>
                                    </div>
                                    <div class="flex items-start sm:items-center">
                                        <flux:badge color="green">Browser Firefox</flux:badge>
                                    </div>
                                </div>
                            </flux:card>
                        </div>

                        <!-- Screenshots Gallery -->
                        <div class="mt-8">
                            <h3 class="text-lg md:text-xl text-zinc-800 dark:text-zinc-100 font-semibold mb-4">
                                Amber App - Nostr Signer
                            </h3>

                            <!-- Responsive Gallery Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                                <!-- Screenshot 1 -->
                                <a href="{{ asset('img/1.png') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="group block">
                                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md hover:shadow-amber-500/10 transition-all duration-300">
                                        <img src="{{ asset('img/1.png') }}"
                                             alt="Amber App Screenshot 1"
                                             loading="lazy"
                                             class="w-full h-auto object-cover aspect-9/16 md:aspect-9/18 group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-linear-to-t from-zinc-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <div class="absolute top-3 left-3 right-3">
                                                <h4 class="text-black text-sm md:text-base font-semibold drop-shadow-lg">
                                                    Startseite
                                                </h4>
                                            </div>
                                            <div class="absolute bottom-3 left-3 right-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-sharp-duotone fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                                                    <span class="text-white text-xs font-medium">Vollbild</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>

                                <!-- Screenshot 2 -->
                                <a href="{{ asset('img/2.png') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="group block">
                                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md hover:shadow-amber-500/10 transition-all duration-300">
                                        <img src="{{ asset('img/2.png') }}"
                                             alt="Amber App Screenshot 2"
                                             loading="lazy"
                                             class="w-full h-auto object-cover aspect-9/16 md:aspect-9/18 group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-linear-to-t from-zinc-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <div class="absolute top-3 left-3 right-3">
                                                <h4 class="text-black text-sm md:text-base font-semibold drop-shadow-lg">
                                                    Profileinstellungen
                                                </h4>
                                            </div>
                                            <div class="absolute bottom-3 left-3 right-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-sharp-duotone fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                                                    <span class="text-white text-xs font-medium">Vollbild</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>

                                <!-- Screenshot 3 -->
                                <a href="{{ asset('img/3.png') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="group block">
                                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md hover:shadow-amber-500/10 transition-all duration-300">
                                        <img src="{{ asset('img/3.png') }}"
                                             alt="Amber App Screenshot 3"
                                             loading="lazy"
                                             class="w-full h-auto object-cover aspect-9/16 md:aspect-9/18 group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-linear-to-t from-zinc-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <div class="absolute top-3 left-3 right-3">
                                                <h4 class="text-black text-sm md:text-base font-semibold drop-shadow-lg">
                                                    Keyverwaltung
                                                </h4>
                                            </div>
                                            <div class="absolute bottom-3 left-3 right-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-sharp-duotone fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                                                    <span class="text-white text-xs font-medium">Vollbild</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>

                                <!-- Screenshot 4 -->
                                <a href="{{ asset('img/4.png') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="group block">
                                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md hover:shadow-amber-500/10 transition-all duration-300">
                                        <img src="{{ asset('img/4.png') }}"
                                             alt="Amber App Screenshot 4"
                                             loading="lazy"
                                             class="w-full h-auto object-cover aspect-9/16 md:aspect-9/18 group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-linear-to-t from-zinc-900/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <div class="absolute top-3 left-3 right-3">
                                                <h4 class="text-black text-sm md:text-base font-semibold drop-shadow-lg">
                                                    Connect/nsec-Bunker
                                                </h4>
                                            </div>
                                            <div class="absolute bottom-3 left-3 right-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-sharp-duotone fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                                                    <span class="text-white text-xs font-medium">Vollbild</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- User Profile Display -->
                        <div class="mt-6">
                            <template x-if="$store.nostr.user">
                                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
                                    <img class="w-12 h-12 rounded-full"
                                         x-bind:src="$store.nostr.user.picture || '{{ asset('apple-touch-icon.png') }}'"
                                         alt="Avatar">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="truncate text-lg leading-snug text-[#1B1B1B] dark:text-zinc-100 font-bold"
                                            x-text="$store.nostr.user.display_name"></h3>
                                        <div class="truncate text-sm text-zinc-500 dark:text-zinc-400"
                                             x-text="$store.nostr.user.name"></div>
                                    </div>
                                </div>
                            </template>

                            @if($currentPubkey && $currentPleb->association_status->value < 2)
                                <flux:card class="mt-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="shrink-0 fill-current text-green-500 mt-0.5" width="16" height="16"
                                             viewBox="0 0 16 16">
                                            <path
                                                d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM7 11.4L3.6 8 5 6.6l2 2 4-4L12.4 6 7 11.4z"></path>
                                        </svg>
                                        <p class="text-sm text-zinc-700 dark:text-zinc-300">Profil in der Datenbank
                                            vorhanden.</p>
                                    </div>
                                </flux:card>
                            @endif
                        </div>
                    </div>
                @endif

                @if($currentPubkey && !$currentPleb->application_for && $currentPleb->association_status->value < 2)
                    <!-- Membership Registration Section -->
                    <div class="space-y-4 py-6">
                        <div>
                            <h3 class="text-xl md:text-2xl text-[#1B1B1B] dark:text-zinc-100 font-bold mb-2">
                                Einundzwanzig Mitglied werden
                            </h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Nur Personen können Mitglied werden und zahlen 21.000 Satoshis im Jahr.
                                <a href="https://einundzwanzig.space/verein/"
                                   class="text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 font-medium">
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
                                <flux:button wire:click="save({{ \App\Enums\AssociationStatus::PASSIVE() }})" variant="primary">
                                    Mit deinem aktuellen Nostr-Profil Mitglied werden
                                </flux:button>
                                <flux:button href="https://einundzwanzig.space/verein/" target="_blank"
                                             variant="outline">
                                    Statuten ansehen
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($currentPubkey)
                    <!-- Email Settings Section -->
                    <div class="py-6">
                        <flux:callout variant="warning" class="mb-6">
                            <div class="space-y-4">
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                    Falls du möchtest, kannst du hier eine E-Mail Adresse hinterlegen, damit der Verein
                                    dich darüber informieren kann, wenn es Neuigkeiten gibt.
                                </p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Am besten eine anonymisierte E-Mail Adresse verwenden. Wir sichern diese Adresse
                                    AES-256 verschlüsselt in der Datenbank ab.
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
                                        <flux:input type="email" wire:model.live.debounce="email"
                                                    placeholder="E-Mail Adresse"/>
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
                        <svg class="shrink-0 fill-current text-green-500 mt-0.5" width="16" height="16"
                             viewBox="0 0 16 16">
                            <path
                                d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                        </svg>
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
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
                            <h3 class="text-xl md:text-2xl text-zinc-800 dark:text-zinc-100 font-bold mb-4">
                                Mitgliedsbeitrag
                            </h3>

                            <flux:callout variant="info" class="mb-6">
                                <p class="text-sm">
                                    Nostr Event für die Zahlung des Mitgliedsbeitrags:
                                    <span
                                        class="block mt-2 font-mono text-xs break-all">{{ $currentPleb->paymentEvents->last()->event_id }}</span>
                                </p>
                            </flux:callout>

                            @php
                                $latestEvent = collect($events)->sortByDesc('created_at')->first();
                            @endphp

                            @if(isset($latestEvent))
                                <p class="text-zinc-700 dark:text-zinc-300 mb-6">{{ $latestEvent['content'] }}</p>

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
                                            Unser Nostr-Relay konnte derzeit nicht erreicht werden, um eine Zahlung zu
                                            initialisieren. Bitte versuche es später noch einmal.
                                        </p>
                                    </div>
                                </flux:callout>
                            @endif
                        </div>

                        <!-- Payment History -->
                        @if($payments && count($payments) > 0)
                            <div class="pt-6 border-t border-zinc-200 dark:border-zinc-600">
                                <h4 class="text-lg md:text-xl text-zinc-800 dark:text-zinc-100 font-bold mb-4">
                                    Bisherige Zahlungen
                                </h4>

                                <!-- Desktop Table -->
                                <div class="hidden md:block overflow-x-auto">
                                    <table class="table-auto w-full">
                                        <thead
                                            class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-600">
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
                                        <tbody class="text-sm divide-y divide-zinc-200 dark:divide-zinc-600">
                                        @foreach($payments as $payment)
                                            <tr>
                                                <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                                    <div
                                                        class="font-medium text-zinc-800 dark:text-zinc-100">{{ $payment->amount }}</div>
                                                </td>
                                                <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                                                    <div
                                                        class="text-zinc-800 dark:text-zinc-100">{{ $payment->year }}</div>
                                                </td>
                                                <td class="px-2 first:pl-5 last:pr-5 py-3">
                                                    <div
                                                        class="font-mono text-xs text-zinc-600 dark:text-zinc-400 break-all">{{ $payment->event_id }}</div>
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
                                        <div
                                            class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-600 p-4">
                                            <div class="space-y-3">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Satoshis</span>
                                                    <span
                                                        class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $payment->amount }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Jahr</span>
                                                    <span
                                                        class="text-zinc-800 dark:text-zinc-100">{{ $payment->year }}</span>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-sm font-medium text-zinc-500 dark:text-zinc-400 block mb-1">Event-ID</span>
                                                    <span
                                                        class="font-mono text-xs text-zinc-600 dark:text-zinc-400 break-all">{{ $payment->event_id }}</span>
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
