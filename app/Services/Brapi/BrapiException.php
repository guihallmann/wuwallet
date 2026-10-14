<?php

declare(strict_types=1);

namespace App\Services\Brapi;

use RuntimeException;
use Throwable;

class BrapiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
