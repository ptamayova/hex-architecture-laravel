<?php

declare(strict_types=1);

namespace Src\Application\Authentication\UseCases;

use Src\Application\Authentication\DTOs\LoginUserInput;
use Src\Application\Authentication\DTOs\UserAuthenticatedDto;
use Src\Domain\Authentication\Exceptions\InvalidCredentialsException;
use Src\Domain\Authentication\Ports\AuthenticatorInterface;
use Src\Domain\Authentication\Ports\PasswordHasherInterface;
use Src\Domain\Authentication\Ports\UserRepositoryInterface;
use Src\Domain\Authentication\ValueObjects\Email;
use Src\Domain\Authentication\ValueObjects\PlainPassword;

final readonly class LoginUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private AuthenticatorInterface $authenticator,
    ) {}

    public function execute(LoginUserInput $input): UserAuthenticatedDto
    {
        $email = new Email($input->email);
        $plainPassword = new PlainPassword($input->password);

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new InvalidCredentialsException();
        }

        if (! $this->passwordHasher->verify($plainPassword, $user->password())) {
            throw new InvalidCredentialsException();
        }

        $this->authenticator->login($user->id());

        return new UserAuthenticatedDto(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
        );
    }
}
