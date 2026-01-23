@props([
    'placeholder' => null,
    'invalid' => null,
])

@php
$classes = Flux::classes()
    ->add('min-w-12 shrink flex-1 outline-none ms-1')
    ->add('placeholder-zinc-400 dark:placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:disabled:placeholder-zinc-500')
    ->add('data-invalid:text-red-500 dark:data-invalid:text-red-400');

$name = $attributes->whereStartsWith('wire:model')->first();

$invalid ??= ($name && $errors->has($name));

$loading = $attributes->whereStartsWith('wire:model.live')->isNotEmpty();

if ($loading) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);
}
@endphp

<input
    type="text"
    {{ $attributes->class($classes) }}
    @if ($invalid) aria-invalid="true" data-invalid @endif
    placeholder="{{ $placeholder }}"
    data-placeholder="{{ $placeholder }}"
    data-flux-pillbox-input
>