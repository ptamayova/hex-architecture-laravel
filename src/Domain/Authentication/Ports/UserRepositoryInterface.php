<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Ports;

use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\Email;
use Src\Authentication\Domain\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function emailExists(Email $email): bool;
}
