<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\ValueObjects;

use InvalidArgumentException;

final readonly class HashedPassword
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Hashed password cannot be empty');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
