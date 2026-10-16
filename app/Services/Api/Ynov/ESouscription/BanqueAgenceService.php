<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\parameter\BanqueAgence;
use Illuminate\Database\Eloquent\Collection;

class BanqueAgenceService
{
    public function getSigleBanques(): Collection
    {
        return BanqueAgence::query()
            ->select('uuid_banque', 'sigle', 'code_banque')
            ->where('etat', 'actif')
            // sigle et code_banque non null ou vide
            ->whereNotNull('sigle')
            ->whereNotNull('code_banque')
            ->where('sigle', '!=', '')
            ->where('code_banque', '!=', '')
            ->distinct('code_banque')
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
