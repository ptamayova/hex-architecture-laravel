<?php

declare(strict_types=1);

namespace Src\Authentication\Application\DTOs;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
