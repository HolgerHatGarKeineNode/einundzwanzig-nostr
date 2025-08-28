<?php

use Livewire\Volt\Component;

use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Event\Event as NostrEvent;
use swentel\nostr\Sign\Sign;
use WireUi\Actions\Notification;

use function Livewire\Volt\{computed, mount, state, with, on, form, updated};
use function Laravel\Folio\{middleware, name};

name('association.profile');

state([
    'no' => false,
    'showEmail' => true,
    'fax' => '',
    'email' => '',
    'yearsPaid' => [],
    'events' => [],
    'payments' => [],
    'amountToPay' => config('app.env') === 'production' ? 21000 : 1,
    'currentYearIsPaid' => false,
    'currentPubkey' => null,
    'currentPleb' => null,
]);

form(\App\Livewire\Forms\ApplicationForm::class);

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->with([
                'paymentEvents' => fn($query)
                    => $query->where('year', date('Y')),
            ])
            ->where('pubkey', $pubkey)->first();
        $this->email = $this->currentPleb->email;
        $this->no = $this->currentPleb->no_email;
        $this->showEmail = !$this->no;
        if ($this->currentPleb->association_status === \App\Enums\AssociationStatus::ACTIVE) {
            $this->amountToPay = config('app.env') === 'production' ? 21000 : 1;
        }
        if ($this->currentPleb->paymentEvents->count() < 1) {
            $this->createPaymentEvent();
            $this->currentPleb->load('paymentEvents');
        }
        $this->loadEvents();
        $this->listenForPayment();
    },
    'nostrLoggedOut' => function () {
        $this->currentPubkey = null;
        $this->currentPleb = null;
        $this->yearsPaid = [];
        $this->events = [];
        $this->payments = [];
        $this->qrCode = null;
        $this->amountToPay = config('app.env') === 'production' ? 21000 : 1;
        $this->currentYearIsPaid = false;
    },
]);

updated([
    'no' => function () {
        $this->showEmail = !$this->no;
        $this->currentPleb->update([
            'no_email' => $this->no,
        ]);
    },
    'fax' => function () {
        $this->js('alert("Markus Turm wird sich per Fax melden!")');
    },
]);

$saveEmail = function () {
    $this->validate([
        'email' => 'required|email',
    ]);
    $this->currentPleb->update([
        'email' => $this->email,
    ]);
    $notification = new Notification($this);
    $notification->success('E-Mail Adresse gespeichert.');
};

$pay = function ($comment) {
    $paymentEvent = $this->currentPleb
        ->paymentEvents()
        ->where('year', date('Y'))
        ->first();
    if ($paymentEvent->btc_pay_invoice) {
        return redirect('https://pay.einundzwanzig.space/i/' . $paymentEvent->btc_pay_invoice);
    }
    try {
        $response = Http::withHeaders([
            'Authorization' => 'token ' . config('services.btc_pay.api_key'),
        ])->post(
            'https://pay.einundzwanzig.space/api/v1/stores/98PF86BoMd3C8P1nHHyFdoeznCwtcm5yehcAgoCYDQ2a/invoices',
            [
                'amount' => $this->amountToPay,
                'metadata' => [
                    'orderId' => $comment,
                    'orderUrl' => url()->route('association.profile'),
                    'itemDesc' => 'Mitgliedsbeitrag ' . date('Y') . ' von nostr:' . $this->currentPleb->npub,
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

        return redirect($response->json()['checkoutLink']);
    } catch (Exception $e) {
        $notification = new Notification($this);
        $notification->error(
            'Fehler beim Erstellen der Rechnung. Bitte versuche es später erneut: ' . $e->getMessage(),
        );
    }
};

$listenForPayment = function () {
    $paymentEvent = $this->currentPleb
        ->paymentEvents()
        ->where('year', date('Y'))
        ->first();
    if ($paymentEvent->btc_pay_invoice) {
        $response = Http::withHeaders([
            'Authorization' => 'token ' . config('services.btc_pay.api_key'),
        ])
            ->get(
                'https://pay.einundzwanzig.space/api/v1/stores/98PF86BoMd3C8P1nHHyFdoeznCwtcm5yehcAgoCYDQ2a/invoices/' . $paymentEvent->btc_pay_invoice,
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
};

$save = function ($type) {
    $this->form->validate();
    if (!$this->form->check) {
        $this->js('alert("Du musst den Statuten zustimmen.")');
        return;
    }

    $this->currentPleb
        ->update([
            'association_status' => $type,
        ]);
};

$createPaymentEvent = function () {
    $note = new NostrEvent();
    $note->setKind(32121);
    $note->setContent(
        'Dieses Event dient der Zahlung des Mitgliedsbeitrags für das Jahr ' . date(
            'Y',
        ) . '. Bitte bezahle den Betrag von ' . number_format($this->amountToPay, 0, ',', '.') . ' Satoshis.',
    );
    $note->setTags([
        ['d', $this->currentPleb->pubkey . ',' . date('Y')],
        ['zap', 'daf83d92768b5d0005373f83e30d4203c0b747c170449e02fea611a0da125ee6', config('services.relay'), '1'],
    ]);
    $signer = new Sign();
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
};

$loadEvents = function () {
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();

    $filter1 = new Filter();
    $filter1->setKinds([32121]);
    $filter1->setAuthors(['daf83d92768b5d0005373f83e30d4203c0b747c170449e02fea611a0da125ee6']);
    $filters = [$filter1];

    $requestMessage = new RequestMessage($subscriptionId, $filters);

    $relays = [
        new Relay(config('services.relay')),
    ];
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);

    $request = new Request($relaySet, $requestMessage);
    $response = $request->send();

    $this->events = collect($response[config('services.relay')])
        ->map(function($event) {
            if(!isset($event->event)) {
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
};

?>

<x-layouts.app
    :seo="new \RalphJSmit\Laravel\SEO\Support\SEOData(title: 'Mitgliedschaft', description: 'Einundzwanzig ist, was du draus machst.')"
>
    @volt
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">

            <!-- Title -->
            <h1 class="text-2xl md:text-3xl text-[#1B1B1B] dark:text-gray-100 font-bold">
                Einundzwanzig ist, was du draus machst
            </h1>

        </div>

        <div class="bg-white dark:bg-[#1B1B1B] shadow-sm rounded-xl mb-8">
            <div class="flex flex-col md:flex-row md:-mr-px">

                <!-- Sidebar -->
                <div
                    class="flex flex-nowrap overflow-x-scroll no-scrollbar md:block md:overflow-auto px-3 py-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700/60 min-w-60 md:space-y-3">
                    <!-- Group 1 -->
                    <div>
                        <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3">
                            Meine Mitgliedschaft
                        </div>
                        <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                            <li class="mr-0.5 md:mr-0 md:mb-0.5">
                                <a class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap bg-[linear-gradient(135deg,var(--tw-gradient-stops))] from-orange-500/[0.12] dark:from-orange-500/[0.24] to-orange-500/[0.04]"
                                   href="#0">
                                    <i class="fa-sharp-duotone fa-solid fa-id-card-clip shrink-0 fill-current text-orange-400 mr-2"></i>
                                    <span
                                        class="text-sm font-medium text-orange-500 dark:text-orange-400">Status</span>
                                </a>
                            </li>
                            {{--<li class="mr-0.5 md:mr-0 md:mb-0.5">
                                <a class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap"
                                   href="notifications.html">
                                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 mr-2" width="16"
                                         height="16" viewBox="0 0 16 16">
                                        <path
                                            d="m9 12.614 4.806 1.374a.15.15 0 0 0 .174-.21L8.133 2.082a.15.15 0 0 0-.268 0L2.02 13.777a.149.149 0 0 0 .174.21L7 12.614V9a1 1 0 1 1 2 0v3.614Zm-1 1.794-5.257 1.503c-1.798.514-3.35-1.355-2.513-3.028L6.076 1.188c.791-1.584 3.052-1.584 3.845 0l5.848 11.695c.836 1.672-.714 3.54-2.512 3.028L8 14.408Z"/>
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200">My Notifications</span>
                                </a>
                            </li>--}}
                        </ul>
                    </div>
                    <!-- Group 2 -->
                    {{--<div>
                        <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3">Experience
                        </div>
                        <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                            <li class="mr-0.5 md:mr-0 md:mb-0.5">
                                <a class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap"
                                   href="feedback.html">
                                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 mr-2" width="16"
                                         height="16" viewBox="0 0 16 16">
                                        <path
                                            d="M14.3.3c.4-.4 1-.4 1.4 0 .4.4.4 1 0 1.4l-8 8c-.2.2-.4.3-.7.3-.3 0-.5-.1-.7-.3-.4-.4-.4-1 0-1.4l8-8zM15 7c.6 0 1 .4 1 1 0 4.4-3.6 8-8 8s-8-3.6-8-8 3.6-8 8-8c.6 0 1 .4 1 1s-.4 1-1 1C4.7 2 2 4.7 2 8s2.7 6 6 6 6-2.7 6-6c0-.6.4-1 1-1z"/>
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200">Give Feedback</span>
                                </a>
                            </li>
                        </ul>
                    </div>--}}
                </div>

                <!-- Panel -->
                <div class="grow">

                    <!-- Panel body -->
                    <div class="p-6 space-y-6">
                        <h2 class="sm:text-2xl text-[#1B1B1B] dark:text-gray-100 font-bold mb-5">Aktueller Status</h2>

                        <section>

                            <div class="space-y-2 mb-12">
                                <div class="flex justify-between items-center mb-4">
                                    <div class="text-xl text-gray-500 dark:text-gray-400 italic">Empfohlene Nostr Login und Signer-Apps</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl px-5 py-4">
                                    <div class="md:flex justify-between items-center space-y-4 md:space-y-0 space-x-2">
                                        <!-- Left side -->
                                        <div class="flex items-start space-x-3 md:space-x-4">
                                            <div>
                                                <a class="inline-flex font-semibold text-gray-800 dark:text-gray-100" href="https://github.com/greenart7c3/Amber">
                                                    Amber
                                                </a>
                                                <div class="text-sm">Perfekt für mobile Android Geräte. Eine App, in der man alle Keys/nsecs verwalten kann.</div>
                                            </div>
                                        </div>
                                        <!-- Right side -->
                                        <div class="flex items-center space-x-4 pl-10 md:pl-0">
                                            <div class="text-xs inline-flex font-medium bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                                                Android
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl px-5 py-4">
                                    <div class="md:flex justify-between items-center space-y-4 md:space-y-0 space-x-2">
                                        <!-- Left side -->
                                        <div class="flex items-start space-x-3 md:space-x-4">
                                            <div>
                                                <a class="inline-flex font-semibold text-gray-800 dark:text-gray-100" href="https://addons.mozilla.org/en-US/firefox/addon/alby/">
                                                    Alby - Bitcoin Lightning Wallet & Nostr
                                                </a>
                                                <div class="text-sm">
                                                    Browser-Erweiterung in die man seinen Key/nsec eingeben kann. Pro Alby-Konto ein nsec.
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Right side -->
                                        <div class="flex items-center space-x-4 pl-10 md:pl-0">
                                            <div class="text-xs inline-flex font-medium bg-yellow-500/20 text-yellow-700 rounded-full text-center px-2.5 py-1">
                                                Browser Chrome/Firefox
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl px-5 py-4">
                                    <div class="md:flex justify-between items-center space-y-4 md:space-y-0 space-x-2">
                                        <!-- Left side -->
                                        <div class="flex items-start space-x-3 md:space-x-4">
                                            <div>
                                                <a class="inline-flex font-semibold text-gray-800 dark:text-gray-100" href="https://chromewebstore.google.com/detail/nos2x/kpgefcfmnafjgpblomihpgmejjdanjjp">
                                                    nos2x
                                                </a>
                                                <div class="text-sm">
                                                    Browser-Erweiterung für Chrome Browser. Multi-Key fähig.
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Right side -->
                                        <div class="flex items-center space-x-4 pl-10 md:pl-0">
                                            <div class="text-xs inline-flex font-medium bg-red-500/20 text-red-700 rounded-full text-center px-2.5 py-1">
                                                Browser Chrome
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl px-5 py-4">
                                    <div class="md:flex justify-between items-center space-y-4 md:space-y-0 space-x-2">
                                        <!-- Left side -->
                                        <div class="flex items-start space-x-3 md:space-x-4">
                                            <div>
                                                <a class="inline-flex font-semibold text-gray-800 dark:text-gray-100" href="https://addons.mozilla.org/en-US/firefox/addon/nos2x-fox/">
                                                    nos2x-fox
                                                </a>
                                                <div class="text-sm">
                                                    Browser-Erweiterung für Firefox Browser. Multi-Key fähig.
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Right side -->
                                        <div class="flex items-center space-x-4 pl-10 md:pl-0">
                                            <div class="text-xs inline-flex font-medium bg-amber-500/20 text-amber-700 rounded-full text-center px-2.5 py-1">
                                                Browser Firefox
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap space-y-2 sm:space-y-0 items-center justify-between">
                                <x-button label="Mit Nostr verbinden" @click="openNostrLogin"
                                          x-show="!$store.nostr.user"/>
                                {{-- https://v.nostr.build/bomfuwLnOTIDrP4y.mp4 --}}
                                <template x-if="$store.nostr.user">
                                    <div class="flex items">
                                        <img class="w-12 h-12 rounded-full"
                                             x-bind:src="$store.nostr.user.picture"
                                             alt="">
                                        <div class="ml-4">
                                            <h3 class="w-48 sm:w-full truncate text-lg leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold"
                                                x-text="$store.nostr.user.display_name"></h3>
                                            <div
                                                class="w-48 sm:w-full truncate text-sm text-gray-500 dark:text-gray-400"
                                                x-text="$store.nostr.user.name"></div>
                                        </div>
                                    </div>
                                </template>
                                @if($currentPubkey && $currentPleb->association_status->value < 2)
                                    <div
                                        class="inline-flex min-w-80 px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-100">
                                        <div class="flex w-full justify-between items-start">
                                            <div class="flex">
                                                <svg class="shrink-0 fill-current text-green-500 mt-[3px] mr-3"
                                                     width="16" height="16" viewBox="0 0 16 16">
                                                    <path
                                                        d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM7 11.4L3.6 8 5 6.6l2 2 4-4L12.4 6 7 11.4z"></path>
                                                </svg>
                                                <div>Profil in der Datenbank vorhanden.</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        {{--<section>
                            @if($currentPubkey && !$currentPleb->application_for && $currentPleb->association_status->value < 2)
                                <h3 class="text-xl leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold mb-1">
                                    passives Mitglied werden
                                </h3>
                                <h4 class="text-xs leading-snug text-[#1B1B1B] dark:text-gray-100 font-italic mb-1">
                                    Passivmitglieder haben kein Stimmrecht. Firmen können nur Passivmitglieder werden und zahlen das 100-fache des festgelegten Beitrags.
                                </h4>
                                <div class="text-sm">
                                    <x-textarea
                                            corner="Beschreibe deine Motivation, passives Mitglied zu werden."
                                            label="Warum möchtest du passives Mitglied werden?"
                                            wire:model="form.reason"/>
                                </div>
                                <div class="sm:flex sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mt-5">
                                    <div class="sm:w-1/3 flex flex-col space-y-2">
                                        <x-button label="Für passive Mitgliedschaft bewerben"
                                                  wire:click="save({{ \App\Enums\AssociationStatus::PASSIVE() }})"/>
                                        <x-badge outline
                                                 label="Es wird im Anschluss ein Nostr Event erzeugt, das du mit dem Mitgliedsbeitrag zappen kannst, nachdem du bestätigt wurdest."/>
                                    </div>
                                </div>
                            @endif
                        </section>--}}

                        <section>
                            @if($currentPubkey && !$currentPleb->application_for && $currentPleb->association_status->value < 2)
                                <h3 class="text-xl leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold mb-1">
                                    Einundzwanzig Mitglied werden
                                </h3>
                                <h4 class="text-xs leading-snug text-[#1B1B1B] dark:text-gray-100 font-italic mb-1">
                                    Nur Personen können Mitglied werden und zahlen 21.000 Satoshis im Jahr.<br>
                                    <a href="https://einundzwanzig.space/verein/" class="text-amber-500">Firmen melden
                                        sich bitte direkt an den Vorstand.</a>
                                </h4>
                                <div class="sm:flex sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mt-5">
                                    <div class="sm:w-1/2 flex flex-col space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <div>
                                                <x-checkbox wire:model="form.check"
                                                            label="Ich stimme den Vereins-Statuten zu"/>
                                            </div>
                                            <div>
                                                <a href="https://einundzwanzig.space/verein/" target="_blank"
                                                   class="text-amber-500">Statuten</a>
                                            </div>
                                        </div>
                                        <x-button label="Mit deinem aktuellen Nostr-Profil Mitglied werden"
                                                  wire:click="save({{ \App\Enums\AssociationStatus::PASSIVE() }})"/>
                                    </div>
                                </div>
                            @endif
                            @if($currentPubkey)
                                <div
                                    class="mt-6 inline-flex flex-col w-full px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400">
                                    <div class="flex w-full justify-between items-start">
                                        <div class="flex w-full">
                                            <svg class="shrink-0 fill-current text-yellow-500 mt-[3px] mr-3"
                                                 width="16"
                                                 height="16" viewBox="0 0 16 16">
                                                <path
                                                    d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                                            </svg>
                                            <div class="w-full">
                                                <div
                                                    class="w-full font-medium text-gray-800 dark:text-gray-100 mb-1">
                                                    Falls du möchtest, kannst du hier eine E-Mail Adresse
                                                    hinterlegen,
                                                    damit der Verein dich darüber informieren kann, wenn es
                                                    Neuigkeiten
                                                    gibt.<br><br>
                                                    Am besten eine anynomisierte E-Mail Adresse verwenden. Wir
                                                    sichern
                                                    diese Adresse AES-256 verschlüsselt in der Datenbank ab.
                                                </div>
                                                <div
                                                    class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 text-amber-500">
                                                    <x-toggle xl warning
                                                              wire:model.live="no"
                                                              label="NEIN">
                                                        <x-slot name="description">
                                                            <span class="py-2 text-amber-500">Ich informiere mich selbst in der News Sektion und gebe keine E-Mail Adresse raus.</span>
                                                        </x-slot>
                                                    </x-toggle>
                                                </div>
                                                @if($showEmail)
                                                    <div wire:key="showEmail"
                                                         class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                                                        <x-input wire:model.live.debounce="fax" label="Fax-Nummer"/>
                                                        <x-input wire:model.live.debounce="email"
                                                                 label="E-Mail Adresse"/>
                                                    </div>
                                                    <div wire:key="showSave" class="flex space-x-2 mt-2">
                                                        <x-button wire:click="saveEmail" label="Speichern"/>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <section>
                            @if($currentPubkey && $currentPleb->application_for)
                                <div
                                    class="inline-flex flex-col w-full max-w-lg px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400">
                                    <div class="flex w-full justify-between items-start">
                                        <div class="flex">
                                            <svg class="shrink-0 fill-current text-yellow-500 mt-[3px] mr-3" width="16"
                                                 height="16" viewBox="0 0 16 16">
                                                <path
                                                    d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                                            </svg>
                                            <div>
                                                <div class="font-medium text-gray-800 dark:text-gray-100 mb-1">
                                                    Du hast dich erfolgreich mit folgendem Grund beworben:
                                                </div>
                                                <div>{{ $currentPleb->application_text }}</div>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-800 dark:text-gray-100 mb-1">
                                                    Schaue später vorbei, denn nun muss jemand aus dem Vorstand deine
                                                    Bewerbung prüfen.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <section>
                            @if($currentPleb && $currentPleb->association_status->value > 1)
                                <div class="flex flex-col space-y-4">
                                    <div
                                        class="inline-flex flex-col w-full max-w-lg px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400">
                                        <div class="flex w-full justify-between items-start">
                                            <div class="flex">
                                                <svg class="shrink-0 fill-current text-yellow-500 mt-[3px] mr-3"
                                                     width="16"
                                                     height="16" viewBox="0 0 16 16">
                                                    <path
                                                        d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                                                </svg>
                                                <div>
                                                    <div class="font-medium text-gray-800 dark:text-gray-100 mb-1">
                                                        Du bist derzeit ein Mitglied des Vereins.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <section>
                            @if($currentPleb && $currentPleb->association_status->value > 1)
                                <div
                                    class="inline-flex flex-col w-full px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400">
                                    <div class="flex w-full justify-between items-start">
                                        <div class="flex">
                                            <svg class="shrink-0 fill-current text-yellow-500 mt-[3px] mr-3" width="16"
                                                 height="16" viewBox="0 0 16 16">
                                                <path
                                                    d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                                            </svg>
                                            <div>
                                                <div
                                                    class="font-medium text-gray-800 dark:text-gray-100 mb-1 space-y-2">
                                                    <p>Nostr Event für die Zahlung des
                                                        Mitgliedsbeitrags: <span
                                                            class="break-all">{{ $currentPleb->paymentEvents->last()->event_id }}</span>
                                                    </p>
                                                    <div>
                                                        @php
                                                            // latest event by created_at field of $events
                                                            $latestEvent = collect($events)->sortByDesc('created_at')->first();
                                                        @endphp
                                                        @if(isset($latestEvent))
                                                            <p>{{ $latestEvent['content'] }}</p>
                                                            <div class="mt-8">
                                                                @if(!$currentYearIsPaid)
                                                                    <div class="flex justify-center">
                                                                        <button
                                                                            wire:click="pay('{{ date('Y') }}:{{ $currentPubkey }}')"
                                                                            class="btn text-2xl dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-green-500"
                                                                        >
                                                                            <i class="fa-sharp-duotone fa-solid fa-bolt-lightning mr-2"></i>
                                                                            Pay {{ $amountToPay }} Sats
                                                                        </button>
                                                                    </div>
                                                                @else
                                                                    @if($currentYearIsPaid)
                                                                        <div class="flex sm:justify-center">
                                                                            <div
                                                                                class="btn sm:text-2xl dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 text-green-500"
                                                                            >
                                                                                <i class="fa-sharp-duotone fa-solid fa-check-circle mr-2"></i>
                                                                                aktuelles Jahr bezahlt
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="flex sm:justify-center">
                                                                <button
                                                                    class="btn dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-amber-500"
                                                                >
                                                                    <i class="fa-sharp-duotone fa-solid fa-user-helmet-safety mr-2"></i>
                                                                    Unser Nostr-Relay konnte derzeit nicht erreicht werden, um eine Zahlung zu initialisieren. Bitte versuche es später noch einmal.
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <section>
                                                        <h3 class="text-xl leading-snug text-gray-800 dark:text-gray-100 font-bold mb-1">
                                                            bisherige Zahlungen</h3>
                                                        <!-- Table -->
                                                        <table class="table-auto w-full dark:text-gray-400">
                                                            <!-- Table header -->
                                                            <thead
                                                                class="text-xs uppercase text-gray-400 dark:text-gray-500">
                                                            <tr class="flex flex-wrap md:table-row md:flex-no-wrap">
                                                                <th class="w-full hidden md:w-auto md:table-cell py-2">
                                                                    <div class="font-semibold text-left">Satoshis</div>
                                                                </th>
                                                                <th class="w-full hidden md:w-auto md:table-cell py-2">
                                                                    <div class="font-semibold text-left">Jahr</div>
                                                                </th>
                                                                <th class="w-full hidden md:w-auto md:table-cell py-2">
                                                                    <div class="font-semibold text-left">Event-ID</div>
                                                                </th>
                                                                <th class="w-full hidden md:w-auto md:table-cell py-2">
                                                                    <div class="font-semibold text-left">Quittung</div>
                                                                </th>
                                                            </tr>
                                                            </thead>
                                                            <!-- Table body -->
                                                            <tbody class="text-sm">
                                                            @foreach($payments as $payment)
                                                                <tr class="flex flex-wrap md:table-row md:flex-no-wrap border-b border-gray-200 dark:border-gray-700/60 py-2 md:py-0">
                                                                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                                                                        <div
                                                                            class="text-left font-medium text-gray-800 dark:text-gray-100">
                                                                            <span class="sm:hidden">Sats:</span>
                                                                            {{ $payment->amount }}
                                                                        </div>
                                                                    </td>
                                                                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                                                                        <div
                                                                            class="text-left"><span
                                                                                class="sm:hidden">Jahr:</span>{{ $payment->year }}
                                                                        </div>
                                                                    </td>
                                                                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                                                                        <div
                                                                            class="text-left font-medium break-all">{{ $payment->event_id }}</div>
                                                                    </td>
                                                                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                                                                        @if($payment->btc_pay_invoice)
                                                                            <x-button target="_blank" xs
                                                                                      label="Quittung"
                                                                                      href="https://pay.einundzwanzig.space/i/{{ $payment->btc_pay_invoice }}/receipt"/>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                            </tbody>
                                                        </table>
                                                    </section>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                    </div>

                </div>

            </div>
        </div>

    </div>
    @endvolt
</x-layouts.app>
