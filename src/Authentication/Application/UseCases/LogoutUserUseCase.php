<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCases;

use Src\Authentication\Domain\Ports\AuthenticatorInterface;

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
