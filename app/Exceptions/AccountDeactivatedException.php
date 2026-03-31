<?php

namespace App\Exceptions;

class AccountDeactivatedException extends AuthenticationException
{
    public function __construct(string $message = 'Your account has been deactivated. Please contact support.')
    {
        parent::__construct($message, 403, [
            'email' => [$message],
        ]);
    }
}
