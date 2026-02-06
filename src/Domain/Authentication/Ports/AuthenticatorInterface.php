<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\Ports;

use Src\Domain\Authentication\ValueObjects\UserId;

interface AuthenticatorInterface
{
    public function login(UserId $userId): void;

    public function logout(): void;

    public function currentUserId(): ?UserId;

    public function isAuthenticated(): bool;
}
