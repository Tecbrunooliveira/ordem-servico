@props([
    'wireModel' => 'cnpj',
    'applyMethod' => 'aplicarDadosCnpjMapeados',
    'variant' => 'cliente',
    'label' => 'CNPJ',
    'placeholder' => '00.000.000/0001-00',
    'hint' => 'Digite o CNPJ para buscar os dados automaticamente',
    'autoFetch' => true,
])

<div
    {{ $attributes->class(['flex flex-col gap-3 sm:flex-row sm:items-end']) }}
    x-data="cnpjLookupField(@js([
        'wireModel' => $wireModel,
        'applyMethod' => $applyMethod,
        'variant' => $variant,
        'autoFetch' => (bool) $autoFetch,
    ]))"
>
    <div class="min-w-0 flex-1">
        <x-maskable
            wire:model.blur="{{ $wireModel }}"
            x-on:keydown.enter.prevent="buscarCnpj(true)"
            :label="$label"
            mask="##.###.###/####-##"
            emit-formatted
            :placeholder="$placeholder"
            :hint="$hint"
        />
    </div>

    <x-button
        secondary
        icon="magnifying-glass"
        label="Buscar CNPJ"
        x-on:click="buscarCnpj(true)"
        x-bind:disabled="buscandoCnpj"
        class="shrink-0"
    />
</div>
