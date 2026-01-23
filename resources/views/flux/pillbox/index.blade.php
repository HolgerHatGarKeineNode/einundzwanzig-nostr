@props([
    'variant' => 'default',
])

<flux:with-field :$attributes>
    <flux:delegate-component :component="'pillbox.variants.' . $variant">{{ $slot }}</flux:delegate-component>
</flux:with-field>
