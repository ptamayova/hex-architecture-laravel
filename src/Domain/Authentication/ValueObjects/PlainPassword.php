<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\ValueObjects;

use InvalidArgumentException;

final readonly class PlainPassword
{
    private string $value;

    public function __construct(string $value)
    {
        if (mb_strlen($value) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
