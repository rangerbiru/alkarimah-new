<?php

namespace App\Services;

use RuntimeException;

class CancellationException extends RuntimeException
{
    /**
     * @param  array<int, array{code: string, message: string}>  $blockers
     */
    public function __construct(public readonly array $blockers)
    {
        parent::__construct(implode(' ', array_column($blockers, 'message')));
    }

    /** @return array<int, string> */
    public function codes(): array
    {
        return array_column($this->blockers, 'code');
    }
}
