@props([
    'value',
    'label',
    'hint',
    'hintColor' => 'text-slate-500',
    'icon',
    'iconBg' => 'bg-blue-100',
    'iconColor' => 'text-blue-600',
])

<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-3xl font-bold text-slate-900">{{ $value }}</p>
            <p class="mt-1 text-sm font-medium text-slate-600">{{ $label }}</p>
            @if ($hint)
                <p class="mt-1 text-xs font-medium {{ $hintColor }}">{{ $hint }}</p>
            @endif
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $iconBg }} {{ $iconColor }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
</div>
