<?php

declare(strict_types=1);

namespace Src\Infrastructure\Authentication;

use Illuminate\Support\Facades\Hash;
use Src\Domain\Authentication\Ports\PasswordHasherInterface;
use Src\Domain\Authentication\ValueObjects\HashedPassword;
use Src\Domain\Authentication\ValueObjects\PlainPassword;

final class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function hash(PlainPassword $password): HashedPassword
    {
        $hashed = Hash::make($password->value());

        return new HashedPassword($hashed);
    }

    public function verify(PlainPassword $plainPassword, HashedPassword $hashedPassword): bool
    {
        return Hash::check($plainPassword->value(), $hashedPassword->value());
    }
}
