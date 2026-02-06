<?php

declare(strict_types=1);

namespace Src\Application\Authentication\DTOs;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
