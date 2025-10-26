<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCases;

use Src\Authentication\Application\DTOs\LoginUserInput;
use Src\Authentication\Application\DTOs\UserAuthenticatedDto;
use Src\Authentication\Domain\Exceptions\InvalidCredentialsException;
use Src\Authentication\Domain\Ports\AuthenticatorInterface;
use Src\Authentication\Domain\Ports\PasswordHasherInterface;
use Src\Authentication\Domain\Ports\UserRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\Email;
use Src\Authentication\Domain\ValueObjects\PlainPassword;

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
