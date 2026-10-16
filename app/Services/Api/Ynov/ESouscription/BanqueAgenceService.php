<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\parameter\BanqueAgence;
use Illuminate\Database\Eloquent\Collection;

class BanqueAgenceService
{
    public function getSigleBanques(): Collection
    {
        return BanqueAgence::query()
            ->selectRaw('MIN(uuid_banque) AS uuid_banque, MIN(TRIM(sigle)) AS sigle, TRIM(code_banque) AS code_banque')
            ->where('etat', 'actif')
            ->whereNotNull('sigle')
            ->whereNotNull('code_banque')
            ->whereRaw("TRIM(sigle) <> ''")
            ->whereRaw("TRIM(code_banque) <> ''")
            ->groupByRaw('TRIM(code_banque)')
            ->orderBy('sigle', 'asc')
            ->get();
    }

    public function getGuichetsByCodeBanque(string $codeBanque): Collection
    {
        return BanqueAgence::query()
            ->where('etat', 'actif')
            ->where('code_banque', $codeBanque)
            ->orderBy('sigle')
            ->get();
    }
}
