<?php

namespace App\Livewire\Association;

use App\Enums\AssociationStatus;
use App\Livewire\Forms\ApplicationForm;
use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
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
use WireUi\Actions\Notification;

final class Profile extends Component
{
    public ApplicationForm $form;

    public bool $no = false;

    public bool $showEmail = true;

    public string $fax = '';

    public string $email = '';

    public array $yearsPaid = [];

    public array $events = [];

    public \Illuminate\Database\Eloquent\Collection $payments;

    public int $amountToPay;

    public bool $currentYearIsPaid = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

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
            $this->email = $this->currentPleb->email;
            $this->no = $this->currentPleb->no_email;
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

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);

        $this->currentPubkey = $pubkey;
        $this->currentPleb = EinundzwanzigPleb::query()
            ->with([
                'paymentEvents' => fn ($query) => $query->where('year', date('Y')),
            ])
            ->where('pubkey', $pubkey)->first();
        $this->email = $this->currentPleb->email;
        $this->no = $this->currentPleb->no_email;
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

    public function handleNostrLoggedOut(): void
    {
        NostrAuth::logout();

        $this->currentPubkey = null;
        $this->currentPleb = null;
        $this->yearsPaid = [];
        $this->events = [];
        $this->payments = [];
        $this->qrCode = null;
        $this->amountToPay = config('app.env') === 'production' ? 21000 : 1;
        $this->currentYearIsPaid = false;
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
        $notification = new Notification($this);
        $notification->success('E-Mail Adresse gespeichert.');
    }

    public function pay($comment): \Illuminate\Http\RedirectResponse
    {
        $paymentEvent = $this->currentPleb
            ->paymentEvents()
            ->where('year', date('Y'))
            ->first();
        if ($paymentEvent->btc_pay_invoice) {
            return redirect('https://pay.einundzwanzig.space/i/'.$paymentEvent->btc_pay_invoice);
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

            return redirect($response->json()['checkoutLink']);
        } catch (\Exception $e) {
            $notification = new Notification($this);
            $notification->error(
                'Fehler beim Erstellen der Rechnung. Bitte versuche es später erneut: '.$e->getMessage(),
            );
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
