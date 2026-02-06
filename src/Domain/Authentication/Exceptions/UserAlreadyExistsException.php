<?php

declare(strict_types=1);

namespace Src\Domain\Authentication\Exceptions;

use Src\Domain\Authentication\ValueObjects\Email;

final class UserAlreadyExistsException extends DomainException
{
    public function __construct(Email $email)
    {
        parent::__construct("A user with email {$email->value()} already exists.");
    }
}
