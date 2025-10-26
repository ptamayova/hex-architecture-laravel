<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Entities;

use Src\Authentication\Domain\ValueObjects\Email;
use Src\Authentication\Domain\ValueObjects\HashedPassword;
use Src\Authentication\Domain\ValueObjects\UserId;

final readonly class User
{
    public function __construct(
        private UserId $id,
        private string $name,
        private Email $email,
        private HashedPassword $password,
    ) {}

    public static function create(
        UserId $id,
        string $name,
        Email $email,
        HashedPassword $password,
    ): self {
        return new self($id, $name, $email, $password);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }
}
