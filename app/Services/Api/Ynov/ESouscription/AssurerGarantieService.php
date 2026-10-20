<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\AssurerGarantie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssurerGarantieService
{
    public function create(array $data): AssurerGarantie
    {
        return DB::transaction(function () use ($data): AssurerGarantie {
            $data['uuid'] = (string) Str::uuid();

            return AssurerGarantie::create($data);
        });
    }
}
