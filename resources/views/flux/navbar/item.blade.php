@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@aware([ 'variant' ])

@props([
    'iconVariant' => 'outline',
    'iconTrailing' => null,
    'badgeColor' => null,
    'variant' => null,
    'iconDot' => null,
    'accent' => true,
    'square' => null,
    'badge' => null,
    'icon' => null,
])

@php
// Button should be a square if it has no text contents...
$square ??= $slot->isEmpty();

// Size-up icons in square/icon-only buttons...
$iconClasses = Flux::classes($square ? 'size-6' : 'size-5');

$classes = Flux::classes()
    ->add('px-3 h-8 flex items-center rounded-lg')
    ->add($square ? '' : 'px-2.5!')
    ->add('text-text-secondary')
    ->add([
        '[--hover-fill:var(--color-bg-elevated)]',
    ])
    ->add(match ($accent) {
        true => [
            'hover:text-text-primary',
            'data-current:text-text-primary hover:data-current:text-text-primary hover:bg-bg-elevated hover:data-current:bg-(--hover-fill)',
        ],
        false => [
            'hover:text-text-primary',
            'data-current:text-text-primary hover:bg-bg-elevated',
        ],
    })
    ;
@endphp

<flux:button-or-link :attributes="$attributes->class($classes)" data-flux-navbar-items>
    <?php if ($icon): ?>
        <div class="relative">
            <?php if (is_string($icon) && $icon !== ''): ?>
                <flux:icon :$icon :variant="$iconVariant" class="{!! $iconClasses !!}" />
            <?php else: ?>
                {{ $icon }}
            <?php endif; ?>

            <?php if ($iconDot): ?>
                <div class="absolute top-[-2px] end-[-2px]">
                    <div class="size-[6px] rounded-full bg-orange-primary"></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($slot->isNotEmpty()): ?>
        <div class="{{ $icon ? 'ms-3' : '' }} flex-1 text-sm font-medium leading-none whitespace-nowrap [[data-nav-footer]_&]:hidden [[data-nav-sidebar]_[data-nav-footer]_&]:block" data-content>{{ $slot }}</div>
    <?php endif; ?>

    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
        <flux:icon :icon="$iconTrailing" variant="micro" class="size-4 ms-1" />
    <?php elseif ($iconTrailing): ?>
        {{ $iconTrailing }}
    <?php endif; ?>

    <?php if (isset($badge) && $badge !== ''): ?>
        <?php $badgeAttributes = Flux::attributesAfter('badge:', $attributes, ['color' => $badgeColor, 'class' => 'ms-2']); ?>
        <flux:navbar.badge :attributes="$badgeAttributes">{{ $badge }}</flux:navbar.badge>
    <?php endif; ?>
</flux:button-or-link>
