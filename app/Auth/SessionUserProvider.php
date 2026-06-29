<?php

namespace App\Auth;

use App\Support\UsuarioStore;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class SessionUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?SessionUser
    {
        $usuario = UsuarioStore::find((int) $identifier);

        return $usuario ? new SessionUser($usuario) : null;
    }

    public function retrieveByToken($identifier, $token): ?SessionUser
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void {}

    public function retrieveByCredentials(array $credentials): ?SessionUser
    {
        if (empty($credentials['email'])) {
            return null;
        }

        $usuario = UsuarioStore::findByEmail($credentials['email']);

        return $usuario ? new SessionUser($usuario) : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $senha = $credentials['password'] ?? '';

        if ($senha === '') {
            return false;
        }

        return Hash::check($senha, $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {}
}
