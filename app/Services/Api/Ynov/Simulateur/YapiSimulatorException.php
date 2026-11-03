<?php

namespace App\Services\Api\Ynov\Simulateur;

use RuntimeException;

class YapiSimulatorException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 503
    ) {
        parent::__construct($message);
    }
}
