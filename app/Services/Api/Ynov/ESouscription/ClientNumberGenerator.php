<?php

namespace App\Services;

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
            // Aucun acteur avec cette racine => on démarre la séquence à 1
            $sequence = 1;
        } else {
            // On extrait la partie séquentielle (les 4 derniers chiffres)
            $partieSequence = (int) substr($dernierIdClient, strlen($racine));
            $sequence = $partieSequence + 1;
        }

        // On formate la séquence sur 4 chiffres (0001, 0002, ...)
        $sequenceFormatee = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        return $racine . $sequenceFormatee;
    }
}