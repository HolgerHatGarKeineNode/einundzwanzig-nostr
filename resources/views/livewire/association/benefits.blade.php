<?php

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use App\Traits\NostrFetcherTrait;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    use NostrFetcherTrait;

    public ?EinundzwanzigPleb $currentPleb = null;

    public ?string $currentPubkey = null;

    public bool $currentYearIsPaid = false;

    public ?string $nip05Handle = '';

    public bool $nip05Verified = false;

    public ?string $nip05VerifiedHandle = null;

    public bool $nip05HandleMismatch = false;

    public array $nip05VerifiedHandles = [];

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
                if ($this->currentPleb->nip05_handle) {
                    $this->nip05Handle = $this->currentPleb->nip05_handle;

                    // Get all NIP-05 handles for current pubkey
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

                // Check if current year is paid
                $paymentEvent = $this->currentPleb->paymentEvents->first();
                if ($paymentEvent && $paymentEvent->paid) {
                    $this->currentYearIsPaid = true;
                }
            }
        }
    }

    public function updatedNip05Handle(): void
    {
        $this->nip05Handle = strtolower($this->nip05Handle);
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

    public function handleNostrLoggedIn(string $pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->mount();
    }

    public function handleNostrLoggedOut(): void
    {
        $this->currentPleb = null;
        $this->currentPubkey = null;
        $this->currentYearIsPaid = false;
        $this->nip05Handle = '';
        $this->nip05Verified = false;
        $this->nip05VerifiedHandle = null;
        $this->nip05HandleMismatch = false;
        $this->nip05VerifiedHandles = [];
    }
}
?>

<div>
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl text-[#1B1B1B] dark:text-zinc-100 font-bold">
            Vorteile deiner Mitgliedschaft
        </h1>
    </div>

    <!-- Benefits Grid - Responsive Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Benefit 1: Nostr Relay -->
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
                                Du hast {{ count($nip05VerifiedHandles) }} aktiv{{ count($nip05VerifiedHandles) > 1 ? 'ierte' : 'isiert' }}e{{ count($nip05VerifiedHandles) > 1 ? ' Handles' : 'es Handle' }} für deinen Pubkey!
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
