<?php

declare(strict_types=1);

namespace Src\Authentication\Application\DTOs;

final readonly class LoginUserInput
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
