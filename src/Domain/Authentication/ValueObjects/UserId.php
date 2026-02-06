<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\ValueObjects;

use InvalidArgumentException;

final readonly class UserId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
