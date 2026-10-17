<?php

namespace App\Services;

use RuntimeException;

class AccountLinkException extends RuntimeException
{
    /** @param  array<int, array{code: string, message: string}>  $blockers */
    public function __construct(string $message, public readonly array $blockers = [])
    {
        parent::__construct($message);
    }
}
