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
