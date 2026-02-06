<?php

declare(strict_types=1);

namespace Src\Application\Authentication\DTOs;

final readonly class LoginUserInput
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
