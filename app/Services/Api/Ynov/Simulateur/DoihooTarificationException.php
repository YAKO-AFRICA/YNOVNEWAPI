<?php

namespace App\Services\Api\Ynov\Simulateur;

use RuntimeException;
use Throwable;

class DoihooTarificationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 502,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
