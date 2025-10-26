<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCases;

use RuntimeException;
use Src\Authentication\Application\DTOs\RegisterUserInput;
use Src\Authentication\Application\DTOs\UserRegisteredDto;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\Exceptions\UserAlreadyExistsException;
use Src\Authentication\Domain\Ports\AuthenticatorInterface;
use Src\Authentication\Domain\Ports\PasswordHasherInterface;
use Src\Authentication\Domain\Ports\UserRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\Email;
use Src\Authentication\Domain\ValueObjects\PlainPassword;
use Src\Authentication\Domain\ValueObjects\UserId;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private AuthenticatorInterface $authenticator,
    ) {}

    public function execute(RegisterUserInput $input): UserRegisteredDto
    {
        $email = new Email($input->email);

        if ($this->userRepository->emailExists($email)) {
            throw new UserAlreadyExistsException($email);
        }

        $plainPassword = new PlainPassword($input->password);
        $hashedPassword = $this->passwordHasher->hash($plainPassword);

        // Create user without ID first (ID will be assigned by database)
        // We'll need to handle this in the infrastructure layer
        $tempUserId = new UserId(1); // Temporary, will be replaced by actual ID
        $user = User::create(
            $tempUserId,
            $input->name,
            $email,
            $hashedPassword
        );

        $this->userRepository->save($user);

        // Get the saved user with actual ID
        $savedUser = $this->userRepository->findByEmail($email);

        if ($savedUser === null) {
            throw new RuntimeException('Failed to retrieve saved user');
        }

        // Authenticate the user
        $this->authenticator->login($savedUser->id());

        return new UserRegisteredDto(
            id: $savedUser->id()->value(),
            name: $savedUser->name(),
            email: $savedUser->email()->value(),
        );
    }
}
