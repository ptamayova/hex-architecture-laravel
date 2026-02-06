<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\Ports;

use Src\Domain\Authentication\Entities\User;
use Src\Domain\Authentication\ValueObjects\Email;
use Src\Domain\Authentication\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function emailExists(Email $email): bool;
}
