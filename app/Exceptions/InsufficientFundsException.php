<?php

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    public function __construct($message = 'Insufficient funds')
    {
        parent::__construct($message, 403);
    }
}
