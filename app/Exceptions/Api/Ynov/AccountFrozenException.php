<?php

namespace App\Exceptions\Api\Ynov;

use Carbon\Carbon;
use Exception;

class AccountFrozenException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $freezeLevel,
        public readonly string $freezeLabel,
        public readonly int $remainingSeconds,
        public readonly ?Carbon $frozenUntil,
    ) {
        parent::__construct($message, 423);
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'code' => 'ACCOUNT_FROZEN',
            'message' => $this->getMessage(),
            'data' => [
                'freeze_level' => $this->freezeLevel,
                'freeze_label' => $this->freezeLabel,
                'remaining_seconds' => $this->remainingSeconds,
                'frozen_until' => $this->frozenUntil?->toIso8601String(),
            ],
        ];
    }
}