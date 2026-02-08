<?php

declare(strict_types=1);

namespace Src\Infrastructure\Authentication\Repositories;

use App\Models\User as EloquentUser;
use Src\Domain\Authentication\Entities\User;
use Src\Domain\Authentication\Ports\UserRepositoryInterface;
use Src\Domain\Authentication\ValueObjects\Email;
use Src\Domain\Authentication\ValueObjects\HashedPassword;
use Src\Domain\Authentication\ValueObjects\UserId;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        EloquentUser::query()->create([
            'name' => $user->name(),
            'email' => $user->email()->value(),
            'password' => $user->password()->value(),
        ]);
    }

    public function findById(UserId $id): ?User
    {
        /** @var EloquentUser|null $eloquentUser */
        $eloquentUser = EloquentUser::query()->find($id->value());

        if ($eloquentUser === null) {
            return null;
        }

        return $this->toDomain($eloquentUser);
    }

    public function findByEmail(Email $email): ?User
    {
        /** @var EloquentUser|null $eloquentUser */
        $eloquentUser = EloquentUser::query()
            ->where('email', $email->value())
            ->first();

        if ($eloquentUser === null) {
            return null;
        }

        return $this->toDomain($eloquentUser);
    }

    public function emailExists(Email $email): bool
    {
        return EloquentUser::query()
            ->where('email', $email->value())
            ->exists();
    }

    private function toDomain(EloquentUser $eloquentUser): User
    {
        return User::create(
            id: new UserId($eloquentUser->id),
            name: $eloquentUser->name,
            email: new Email($eloquentUser->email),
            password: new HashedPassword($eloquentUser->password),
        );
    }
}
