<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\Ports;

use Src\Domain\Authentication\ValueObjects\HashedPassword;
use Src\Domain\Authentication\ValueObjects\PlainPassword;

interface PasswordHasherInterface
{
    public function hash(PlainPassword $password): HashedPassword;

    public function verify(PlainPassword $plainPassword, HashedPassword $hashedPassword): bool;
}
