<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Ports;

use Src\Authentication\Domain\ValueObjects\UserId;

interface AuthenticatorInterface
{
    public function login(UserId $userId): void;

    public function logout(): void;

    public function currentUserId(): ?UserId;

    public function isAuthenticated(): bool;
}
