@php
    $marca = \App\Support\EmpresaConfig::branding();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login — {{ $marca['nome'] }}</title>
    <meta name="description" content="Acesse o painel de gestão técnica e ordens de serviço.">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-page">
    <main class="login-shell">
        <div class="login-container">
            <section class="login-form-panel">
                <div class="login-form-inner">
                    <div @class(['login-brand', 'login-brand--has-logo' => $marca['tem_logo']])>
                        <div @class(['login-brand-mark', 'login-brand-mark--image' => $marca['tem_logo']])>
                            @if ($marca['tem_logo'])
                                <img src="{{ $marca['logo'] }}" alt="{{ $marca['nome'] }}">
                            @else
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="login-brand-text">
                            <p class="login-brand-name">{{ $marca['nome'] }}</p>
                            <p class="login-brand-subtitle">{{ $marca['subtitulo'] }}</p>
                        </div>
                    </div>

                    <div class="login-card">
                        <div class="login-card-header">
                            <h1 class="login-title">Seja bem-vindo de volta!</h1>
                            <p class="login-subtitle">Entre com suas credenciais para acessar o painel.</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="login-form">
                            @csrf

                            <div class="login-field-group">
                                <label class="login-field-label" for="email">E-mail</label>
                                <div @class(['login-field', 'is-invalid' => $errors->has('email')])>
                                    <div class="login-field-icon" aria-hidden="true">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input
                                        id="email"
                                        class="login-field-input"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="seu@email.com"
                                        required
                                        autofocus
                                    >
                                </div>
                            </div>

                            <div class="login-field-group">
                                <label class="login-field-label" for="password">Senha</label>
                                <div @class(['login-field', 'is-invalid' => $errors->has('password') || $errors->has('email')])>
                                    <div class="login-field-icon" aria-hidden="true">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                                        </svg>
                                    </div>
                                    <input
                                        id="password"
                                        class="login-field-input"
                                        type="password"
                                        name="password"
                                        placeholder="Digite sua senha"
                                        required
                                    >
                                    <button
                                        id="toggle-password"
                                        type="button"
                                        class="login-toggle-password"
                                        aria-label="mostrar/ocultar senha"
                                    >
                                        <svg data-eye-open width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                        <svg data-eye-closed class="hidden" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @if ($errors->any())
                                <p class="login-error" role="alert">{{ $errors->first() }}</p>
                            @endif

                            <div class="login-options">
                                <label for="remember" class="login-remember">
                                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                                    <span>Lembrar de mim</span>
                                </label>
                                <a href="#" class="login-forgot">Esqueci minha senha</a>
                            </div>

                            <button type="submit" class="login-submit">
                                Entrar no sistema
                            </button>
                        </form>
                    </div>

                    <p class="login-footer-note">Acesso restrito a usuários autorizados.</p>
                </div>
            </section>
        </div>
    </main>

    <script src="{{ asset('js/login.js') }}" defer></script>
</body>
</html>
