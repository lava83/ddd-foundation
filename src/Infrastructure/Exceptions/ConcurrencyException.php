<?php

declare(strict_types=1);

namespace Lava83\DddFoundation\Infrastructure\Exceptions;

use RuntimeException;
use Throwable;

class ConcurrencyException extends RuntimeException
{
    public function __construct(
        string $message = 'Concurrency conflict detected',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
