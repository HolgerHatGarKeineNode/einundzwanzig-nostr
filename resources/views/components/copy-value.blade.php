@props([
    'value',
    'label' => 'Wert',
])

{{--
    Klickbarer Wert, der sich in die Zwischenablage kopiert.

    Als <button> statt als <div> mit Click-Handler: Tastaturbedienung und
    Screenreader-Rolle kommen damit gratis. Die Rueckmeldung laeuft ueber
    aria-live, damit sie auch angesagt wird und nicht nur aufblitzt.

    navigator.clipboard verlangt einen Secure Context (https oder localhost).
    Faellt es aus, bleibt der Wert markierbarer Text und der Nutzer sieht einen
    Hinweis statt einer stillen Nichtreaktion.
--}}
<div x-data="{
        kopiert: false,
        fehler: false,
        async kopieren() {
            try {
                await navigator.clipboard.writeText(@js($value));
                this.kopiert = true;
                this.fehler = false;
                setTimeout(() => this.kopiert = false, 2000);
            } catch (e) {
                this.fehler = true;
                setTimeout(() => this.fehler = false, 4000);
            }
        }
     }">
    <button
        type="button"
        x-on:click="kopieren()"
        aria-label="{{ $label }} in die Zwischenablage kopieren"
        {{ $attributes->class([
            'group flex w-full items-start gap-2 rounded-lg px-2 py-1.5 -mx-2 text-left',
            'transition-colors duration-150 motion-reduce:transition-none',
            'hover:bg-bg-elevated focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
        ]) }}
    >
        <span class="min-w-0 flex-1 break-all text-[11px] font-semibold tracking-[0.08em] text-text-primary">
            {{ $value }}
        </span>
        <flux:icon
            name="clipboard-document"
            variant="micro"
            class="mt-0.5 shrink-0 text-text-tertiary group-hover:text-orange-500 transition-colors duration-150 motion-reduce:transition-none"
            aria-hidden="true"
            x-show="!kopiert"
        />
        <flux:icon
            name="check"
            variant="micro"
            class="mt-0.5 shrink-0 text-green-500"
            aria-hidden="true"
            x-show="kopiert"
            x-cloak
        />
    </button>

    <p class="mt-1 px-0 text-sm text-green-500" x-show="kopiert" x-cloak aria-live="polite">
        Kopiert.
    </p>
    <p class="mt-1 px-0 text-sm text-yellow-400" x-show="fehler" x-cloak aria-live="polite">
        Kopieren nicht möglich — bitte den Wert markieren.
    </p>
</div>
