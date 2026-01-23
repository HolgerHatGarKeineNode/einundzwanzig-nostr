@aware([ 'placeholder' ])

@props([
    'placeholder' => null,
    'clearable' => null,
    'invalid' => null,
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('data-invalid:text-red-500 dark:data-invalid:text-red-400')
    // We don't want to show the red border when invalid, so we need to apply all the default border styles with data-invalid: and important! modifier
    ->add('data-invalid:shadow-xs! data-invalid:border-zinc-200! data-invalid:border-b-zinc-300/80! data-invalid:disabled:border-b-zinc-200! data-invalid:dark:border-white/10! data-invalid:dark:disabled:border-white/5!');
    
$loading = $attributes->whereStartsWith('wire:model.live')->isNotEmpty();

if ($loading) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);
}
@endphp

<flux:input :$size :$placeholder :$attributes :class:input="$classes">
    <x-slot name="iconTrailing">
        <?php if ($clearable): ?>
            <flux:button as="div"
                class="cursor-pointer ms-2 -me-3 [[data-flux-input]:has(input:placeholder-shown)_&]:hidden [[data-flux-select]:has([disabled][data-selected])_&]:hidden"
                variant="subtle"
                :size="$size === 'sm' ? 'xs' : 'sm'"
                square
                tabindex="-1"
                aria-label="Clear selected"
                x-on:click.prevent.stop="let select = $el.closest('ui-select'); select.value = select.hasAttribute('multiple') ? [] : null; select.dispatchEvent(new Event('change', { bubbles: false })); select.dispatchEvent(new Event('input', { bubbles: false }))"
            >
                <flux:icon.x-mark variant="micro" />
            </flux:button>
        <?php endif; ?>

        <flux:button size="sm" square variant="subtle" tabindex="-1" class="-me-1 [[disabled]_&]:pointer-events-none">
            <flux:icon.chevron-up-down variant="mini" class="text-zinc-400/75 [[data-flux-input]:hover_&]:text-zinc-800 [[disabled]_&]:text-zinc-200! dark:text-white/60 dark:[[data-flux-input]:hover_&]:text-white dark:[[disabled]_&]:text-white/40!" />
        </flux:button>
    </x-slot>
</flux:input>
