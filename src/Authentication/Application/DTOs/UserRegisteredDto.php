<?php

declare(strict_types=1);

namespace Src\Authentication\Application\DTOs;

final readonly class UserRegisteredDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}
}
