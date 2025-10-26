<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure;

use Illuminate\Support\Facades\Auth;
use Src\Authentication\Domain\Ports\AuthenticatorInterface;
use Src\Authentication\Domain\ValueObjects\UserId;

final class LaravelAuthenticator implements AuthenticatorInterface
{
    public function login(UserId $userId): void
    {
        Auth::loginUsingId($userId->value());
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function currentUserId(): ?UserId
    {
        $id = Auth::id();

        if ($id === null) {
            return null;
        }

        return new UserId($id);
    }

    public function isAuthenticated(): bool
    {
        return Auth::check();
    }
}
