<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmpresaLogoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/agenda', 'agenda')->name('agenda.index');
    Route::view('/tarefas', 'tarefas')->name('tarefas.index');
    Route::view('/clientes', 'clientes')->name('clientes.index');
    Route::view('/ordens-servico', 'ordens-servico')->name('ordens-servico.index');
    Route::view('/configuracoes', 'configuracoes')->name('configuracoes.index');
    Route::post('/configuracoes/empresa/logo', [EmpresaLogoController::class, 'store'])
        ->name('configuracoes.empresa.logo');
    Route::view('/tecnicos', 'tecnicos')->name('tecnicos.index');
    Route::view('/usuarios', 'usuarios')->name('usuarios.index');
});
