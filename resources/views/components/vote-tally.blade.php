@props([
    'label',
    'votes',
    'tone' => 'approve',
])

{{--
    Eine Zeile der Stimmauswertung: Icon, Wort, Avatare, Zahl.
    Icon UND Wort tragen die Bedeutung — Farbe steht nie allein.
--}}
<div class="flex items-center gap-2 py-1.5">
    <flux:icon :name="$tone === 'approve' ? 'check-circle' : 'x-circle'"
               variant="micro"
               @class([
                   'shrink-0',
                   'text-green-500' => $tone === 'approve',
                   'text-red-400' => $tone !== 'approve',
               ])
               aria-hidden="true"/>

    <span class="text-sm text-text-secondary">{{ $label }}</span>

    <div class="flex-1 flex items-center justify-end -space-x-2">
        @foreach($votes->take(5) as $vote)
            <img class="size-6 rounded-full ring-2 ring-bg-surface"
                 src="{{ $vote->einundzwanzigPleb?->profile?->picture }}"
                 onerror="this.src='{{ asset('einundzwanzig-alpha.jpg') }}'"
                 alt="{{ $vote->einundzwanzigPleb?->profile?->name ?? 'Mitglied' }}"
                 title="{{ $vote->einundzwanzigPleb?->profile?->name ?? str($vote->einundzwanzigPleb?->npub)->limit(16) }}"
                 width="24" height="24" loading="lazy">
        @endforeach
        @if($votes->count() > 5)
            <span class="pl-3 text-[11px] font-semibold text-text-tertiary">+{{ $votes->count() - 5 }}</span>
        @endif
    </div>

    <span class="w-6 shrink-0 text-right text-[11px] font-semibold uppercase tracking-[0.14em] text-text-primary">
        {{ $votes->count() }}
    </span>
</div>
