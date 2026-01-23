@props([
    'name' => null,
    'align' => 'right',
])

@php
// We only want to show the name attribute it has been set manually
// but not if it has been set from the `wire:model` attribute...
$showName = isset($name);
if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$classes = Flux::classes()
    ->add('group h-5 w-8 min-w-8 relative inline-flex items-center outline-offset-2')
    ->add('rounded-full')
    ->add('transition')
    ->add('bg-bg-elevated [&[disabled]]:opacity-50 border border-border-default [&[disabled]]:border-border-subtle')
    ->add('[print-color-adjust:exact]')
    ->add([
        'data-checked:bg-orange-primary',
        'data-checked:border-0',
    ])
    ;

$indicatorClasses = Flux::classes()
    ->add('size-3.5')
    ->add('rounded-full')
    ->add('transition translate-x-[2px] rtl:-translate-x-[2px]')
    ->add('bg-text-secondary')
    ->add([
        'group-data-checked:translate-x-[15px] rtl:group-data-checked:-translate-x-[15px]',
        'group-data-checked:bg-text-primary',
    ]);
@endphp

@if ($align === 'left' || $align === 'start')
    <flux:with-inline-field :$attributes>
        <ui-switch {{ $attributes->class($classes) }} @if($showName) name="{{ $name }}" @endif data-flux-control data-flux-switch>
            <span class="{{ \Illuminate\Support\Arr::toCssClasses($indicatorClasses) }}"></span>
        </ui-switch>
    </flux:with-inline-field>
@else
    <flux:with-reversed-inline-field :$attributes>
        <ui-switch {{ $attributes->class($classes) }} @if($showName) name="{{ $name }}" @endif data-flux-control data-flux-switch>
            <span class="{{ \Illuminate\Support\Arr::toCssClasses($indicatorClasses) }}"></span>
        </ui-switch>
    </flux:with-reversed-inline-field>
@endif
