<?php

namespace App\Services;

use App\Models\Contrat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EncaissementBisService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.api.encaissement_bis');
    }

}