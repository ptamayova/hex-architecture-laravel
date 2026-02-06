<?php

declare(strict_types=1);

namespace Src\Application\Authentication\UseCases;

use RuntimeException;
use Src\Application\Authentication\DTOs\RegisterUserInput;
use Src\Application\Authentication\DTOs\UserRegisteredDto;
use Src\Domain\Authentication\Entities\User;
use Src\Domain\Authentication\Exceptions\UserAlreadyExistsException;
use Src\Domain\Authentication\Ports\AuthenticatorInterface;
use Src\Domain\Authentication\Ports\PasswordHasherInterface;
use Src\Domain\Authentication\Ports\UserRepositoryInterface;
use Src\Domain\Authentication\ValueObjects\Email;
use Src\Domain\Authentication\ValueObjects\PlainPassword;
use Src\Domain\Authentication\ValueObjects\UserId;

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

        $tempUserId = new UserId(1); // Temporary, will be replaced by actual ID from DB
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

        $this->authenticator->login($savedUser->id());

        return new UserRegisteredDto(
            id: $savedUser->id()->value(),
            name: $savedUser->name(),
            email: $savedUser->email()->value(),
        );
    }
}
