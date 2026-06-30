@props([
    'segundos' => 0,
    'running' => false,
    'startedAt' => null,
    'dotClass' => 'bg-slate-300',
    'textClass' => 'text-slate-400',
    'label' => 'Aguardando',
])

<div
    {{ $attributes->class(['inline-flex min-w-[5.5rem] flex-col items-start gap-0.5']) }}
    x-data="ordemCronometro({
        baseSeconds: {{ (int) $segundos }},
        running: @js((bool) $running),
        startedAt: @js($startedAt),
    })"
>
    <span class="inline-flex items-center gap-1.5">
        <span @class(['h-2 w-2 shrink-0 rounded-full', $dotClass])></span>
        <span x-text="display" @class(['font-mono text-sm font-semibold tabular-nums leading-none', $textClass])></span>
    </span>
    <span @class(['text-[10px] font-semibold uppercase tracking-wide leading-none', $textClass])>{{ $label }}</span>
</div>
