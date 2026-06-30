<?php

namespace App\Http\Controllers;

use App\Support\EmpresaConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmpresaLogoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'logo' => ['required', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,svg'],
            ]);

            EmpresaConfig::saveLogoFromUpload($validated['logo']);

            return redirect()
                ->route('configuracoes.index', ['editar' => 'empresa'])
                ->with('logo_ok', 'A imagem foi salva e já aparece no login e no menu.');
        } catch (ValidationException $exception) {
            return redirect()
                ->route('configuracoes.index', ['editar' => 'empresa'])
                ->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('configuracoes.index', ['editar' => 'empresa'])
                ->with(
                    'logo_erro',
                    'Não foi possível salvar a logo. Use PNG ou JPG (até 5 MB) e confira se a pasta storage/app/public/empresa existe no servidor.',
                );
        }
    }
}
