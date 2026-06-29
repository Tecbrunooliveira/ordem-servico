<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Collection;

class SessionUser implements Authenticatable
{
    use Authorizable;

    /** @param  array<string, mixed>  $attributes */
    public function __construct(private array $attributes) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return (int) $this->attributes['id'];
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->attributes[$this->getAuthPasswordName()] ?? '');
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }

    /** @return Collection<int, string> */
    public function getRoleNames(): Collection
    {
        $tipo = $this->attributes['tipo'] ?? 'usuário';

        return collect([ucfirst((string) $tipo)]);
    }

    public function can($abilities, $arguments = []): bool
    {
        $tipo = $this->attributes['tipo'] ?? '';

        if ($tipo === 'administrador') {
            return true;
        }

        if ($tipo === 'cliente') {
            return false;
        }

        return $tipo === 'tecnico';
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }
}
