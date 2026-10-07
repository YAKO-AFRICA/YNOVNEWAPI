<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\Acteur;
use DateTimeInterface;

class ClientNumberGenerator
{

    public function generate(
        string $genre,
        DateTimeInterface $dateNaissance
    ): string {
        if ($genre === 'M') {
            $indice = 1;
        } elseif ($genre === 'F') {
            $indice = 2;
        } else {
            $indice = 1;
        }
        $racine = $indice
            . $dateNaissance->format('y')
            . $dateNaissance->format('m');

        // On cherche le dernier idClient existant qui commence par cette racine
        $dernierIdClient = Acteur::query()
            ->where('idClient', 'LIKE', $racine . '%')
            ->orderByDesc('idClient')
            ->value('idClient');

        if ($dernierIdClient === null) {
            $sequence = 1;
        } else {
            $partieSequence = (int) substr($dernierIdClient, strlen($racine));
            $sequence = $partieSequence + 1;
        }

        $sequenceFormatee = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        return $racine . $sequenceFormatee;
    }
}