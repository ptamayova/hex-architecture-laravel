<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Exceptions;

use Src\Authentication\Domain\ValueObjects\Email;

final class UserAlreadyExistsException extends DomainException
{
    public function __construct(Email $email)
    {
        parent::__construct("A user with email {$email->value()} already exists.");
    }
}
