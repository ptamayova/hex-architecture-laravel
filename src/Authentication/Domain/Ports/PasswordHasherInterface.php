<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Ports;

use Src\Authentication\Domain\ValueObjects\HashedPassword;
use Src\Authentication\Domain\ValueObjects\PlainPassword;

interface PasswordHasherInterface
{
    public function hash(PlainPassword $password): HashedPassword;

    public function verify(PlainPassword $plainPassword, HashedPassword $hashedPassword): bool;
}
