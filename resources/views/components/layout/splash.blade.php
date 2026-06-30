@php
    $empresa = \App\Support\EmpresaConfig::get();
    $nomeMarca = ! empty($empresa['razao_social']) ? $empresa['razao_social'] : ($empresa['nome_empresa'] ?? config('navigation.brand.name'));
    $usuario = auth()->user();
    $nomeUsuario = $usuario?->nome ?? 'Usuário';
    $primeiroNome = explode(' ', trim($nomeUsuario))[0];
@endphp

<div
    id="app-splash"
    class="app-splash fixed inset-0 z-[200] flex flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-[var(--color-sidebar-from)] via-[var(--color-sidebar-via)] to-[var(--color-sidebar-to)] px-6"
    role="status"
    aria-live="polite"
    aria-label="Carregando sistema"
>
    <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-20 h-56 w-56 rounded-full bg-white/[0.04]"></div>

    <div class="app-splash-content relative flex flex-col items-center text-center">
        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 shadow-2xl shadow-black/20 ring-1 ring-white/20 backdrop-blur-sm">
            @if (! empty($empresa['logo']))
                <img src="{{ $empresa['logo'] }}" alt="{{ $nomeMarca }}" class="h-12 w-12 object-contain">
            @else
                <x-icon name="wrench-screwdriver" class="h-10 w-10 text-white" />
            @endif
        </div>

        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-white/70">
            {{ config('navigation.brand.name') }}
        </p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Bem-vindo, {{ $primeiroNome }}!
        </h1>
        <p class="mt-2 max-w-xs text-sm text-white/75">
            Preparando seu painel…
        </p>

        <div class="mt-8 h-1 w-48 overflow-hidden rounded-full bg-white/15">
            <div class="app-splash-bar h-full rounded-full bg-white/90"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var splash = document.getElementById('app-splash');

    if (! splash) {
        return;
    }

    window.setTimeout(function () {
        splash.classList.add('app-splash--hide');
    }, 2600);

    splash.addEventListener('transitionend', function () {
        if (splash.classList.contains('app-splash--hide')) {
            splash.remove();
        }
    });
})();
</script>
