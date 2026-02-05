<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format: {$value}");
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
