<?php

declare(strict_types=1);

namespace Src\Application\Authentication\UseCases;

use Src\Domain\Authentication\Ports\AuthenticatorInterface;

final readonly class LogoutUserUseCase
{
    public function __construct(
        private AuthenticatorInterface $authenticator,
    ) {}

    public function execute(): void
    {
        $this->authenticator->logout();
    }
}
