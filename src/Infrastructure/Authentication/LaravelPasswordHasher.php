<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure;

use Illuminate\Support\Facades\Hash;
use Src\Authentication\Domain\Ports\PasswordHasherInterface;
use Src\Authentication\Domain\ValueObjects\HashedPassword;
use Src\Authentication\Domain\ValueObjects\PlainPassword;

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
