@props([
    'href' => '#',
    'icon',
    'label',
    'active' => false,
    'badge' => null,
    'danger' => false,
    'tag' => 'a',
])

@php
    $isButton = $tag === 'button';
@endphp

<{{ $isButton ? 'button' : 'a' }}
    @if ($isButton)
        type="submit"
    @else
        href="{{ $href }}"
    @endif
    title="{{ $label }}"
    @class([
        'sidebar-link group',
        'sidebar-link--active' => $active && ! $danger,
        'sidebar-link--danger' => $danger,
    ])
    :class="sidebarOpen ? 'sidebar-link--expanded' : 'sidebar-link--collapsed'"
    {{ $attributes->except(['href', 'icon', 'label', 'active', 'badge', 'danger', 'tag']) }}
>
    <span @class([
        'sidebar-link__icon',
        'sidebar-link__icon--active' => $active && ! $danger,
        'sidebar-link__icon--danger' => $danger,
    ])>
        <x-icon :name="$icon" class="sidebar-link__svg" />
    </span>

    <span class="sidebar-link__label truncate" x-show="sidebarOpen" x-cloak>{{ $label }}</span>

    @if ($badge)
        <span
            x-show="sidebarOpen"
            x-cloak
            @class([
                'sidebar-link__badge',
                'sidebar-link__badge--active' => $active,
            ])
        >
            {{ $badge }}
        </span>

        <span
            x-show="! sidebarOpen"
            x-cloak
            class="sidebar-link__badge-dot"
        ></span>
    @endif
</{{ $isButton ? 'button' : 'a' }}>
