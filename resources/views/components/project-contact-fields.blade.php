@props(['form'])

{{--
    Kontaktwunsch des Einreichers. Bewusst vollstaendig optional: wer weder eine
    Nostr-DM noch einen anderen Kanal angibt, waehlt damit den Weg ueber den
    privaten Chatraum des Antrags — kein unvollstaendiges Formular.

    Der Text sagt seit der Einfuehrung dieser Raeume NICHT mehr "dann wirst du
    nicht kontaktiert": Der Einreicher wird dem Raum immer beigefuegt, und eine
    Zusage im Formular, die die Software bricht, waere schlimmer als keine.

    Das x-show haengt an einem rohen <div>, nicht am <flux:field>: Flux-Komponenten
    und rohes HTML haben unterschiedliche Bind-Konventionen, und ein x-show direkt
    an einer Flux-Komponente bindet still nicht.
--}}
<flux:fieldset>
    <flux:legend>Kontakt</flux:legend>

    <div class="space-y-3" x-data="{ dm: @js((bool) ($form['contact_via_nostr_dm'] ?? true)) }">
        <flux:field variant="inline">
            <flux:switch wire:model.live="form.contact_via_nostr_dm" x-model="dm" />
            <flux:label>Kontakt per Nostr-DM erwünscht</flux:label>
        </flux:field>
        <flux:description>
            Der Vorstand meldet sich bei Rückfragen per verschlüsselter Direktnachricht an deinen npub.
        </flux:description>

        <div x-show="!dm" x-cloak>
            <flux:field>
                <flux:label badge="optional">Anderer Kanal</flux:label>
                <flux:input wire:model="form.contact_alternative"
                            placeholder="z. B. E-Mail, Telegram, Matrix" />
                <flux:description>
                    Lässt du das Feld leer, meldet sich der Vorstand nur im privaten
                    Chatraum zu deinem Antrag, zu dem du automatisch Zugang hast.
                </flux:description>
                <flux:error name="form.contact_alternative" />
            </flux:field>
        </div>
    </div>
</flux:fieldset>
