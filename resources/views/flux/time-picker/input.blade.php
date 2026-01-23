@props([
    'variant' => 'outline',
    'clearable' => null,
    'dropdown' => null,
    'invalid' => false,
    'size' => null,
])

@php

$classes = Flux::classes()
    ->add('w-full border rounded-lg block group disabled:shadow-none dark:shadow-none')
    ->add('ps-3 pe-2 flex items-center')
    ->add('font-mono cursor-default')
    ->add(match ($size) {
        default => 'text-base sm:text-sm py-2 h-10 leading-[1.375rem]', // This makes the height of the input 40px (same as buttons and such...)
        'sm' => 'text-sm py-1.5 h-8 leading-[1.125rem]',
        'xs' => 'text-xs py-1.5 h-6 leading-[1.125rem]',
    })
    ->add(match ($variant) { // Background...
        'outline' => 'bg-white dark:bg-white/10 dark:disabled:bg-white/[7%]',
        'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/[7%]',
    })
    ->add(match ($variant) { // Text color
        'outline' => 'text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500',
        'filled'  => 'text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40',
    })
    ->add(match ($variant) { // Border...
        'outline' => $invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5',
        'filled'  => $invalid ? 'border-red-500' : 'border-0',
    })
    ;

$inputClasses = Flux::classes()
    ->add('w-[calc(2ch+2px)] text-center')
    ->add('rounded-sm')
    ->add('disabled:text-zinc-500 dark:disabled:text-zinc-400')
    // The below reverts styles added by Tailwind Forms plugin
    ->add('border-0 bg-transparent p-0 [font-size:inherit] [line-height:inherit] focus:ring-0 focus:ring-offset-0 focus:outline-[revert] focus:outline-offset-[revert]')
    ;

$buttonClasses = Flux::classes()
    ->add(match ($size) {
        default => '!size-8 -mr-1.25 text-base sm:text-sm rounded-md block w-full',
        'sm' => '!size-6 text-sm rounded-md block w-full',
        'xs' => '!size-4 text-xs rounded-md block w-full',
    })
    ->add('[[disabled]_&]:pointer-events-none')
    ;
@endphp


<div {{ $attributes->class($classes) }}>
    {{-- This click.stop prevents clicking on the inputs or the characters between from opening the popover... --}}
    <div x-on:click.stop class="flex items-center" dir="ltr" wire:ignore>
        <input type="text" inputmode="numeric" data-flux-hour-input class="{{ $inputClasses }}" />:
        <input type="text" inputmode="numeric" data-flux-minute-input class="{{ $inputClasses }}" />&nbsp;
        <input type="text" data-flux-meridiem-input class="{{ $inputClasses }}" />
    </div>

    <span class="flex-1"></span>

    <?php if ($clearable): ?>
        <flux:button
            as="div"
            class="cursor-pointer [ui-time-picker:has([disabled])_&]:hidden"
            variant="subtle"
            :size="$size === 'sm' || $size === 'xs' ? 'xs' : 'sm'"
            square
            tabindex="-1"
            aria-label="Clear time"
            x-on:click.prevent.stop="let timePicker = $el.closest('ui-time-picker'); timePicker.clear();"
            inset
        >
            <flux:icon.x-mark variant="micro" />
        </flux:button>
    <?php endif; ?>

    <?php if ($dropdown === false || $dropdown === 'false'): ?>
    <div class="{{ $buttonClasses->add('flex items-center justify-center') }}">
            <flux:icon.clock variant="mini" class="text-zinc-300 [[disabled]_&]:text-zinc-200! dark:text-white/60 dark:[[disabled]_&]:text-white/40!" />
        </div>
    <?php else: ?>
        <flux:button square variant="subtle" class="{{ $buttonClasses }}" data-flux-time-picker-button>
            <flux:icon.clock variant="mini" class="text-zinc-300 [[data-flux-time-picker-button]:hover_&]:text-zinc-800 [[disabled]_&]:text-zinc-200! dark:text-white/60 dark:[[data-flux-time-picker-button]:hover_&]:text-white dark:[[disabled]_&]:text-white/40!" />
        </flux:button>
    <?php endif; ?>
</div>
