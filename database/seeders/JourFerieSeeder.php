<?php
// database/seeders/JourFerieSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\JourFerie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JourFerieSeeder extends Seeder
{
    public function run(): void
    {
        $feries = [
            [
                'date' => '2026-01-01',
                'libelle' => 'Jour de l\'an',
                'est_recurrent' => true,
                'code' => 'NOUVEL_AN',
            ],
            [
                'date' => '2026-04-06',
                'libelle' => 'Lundi de Pâques',
                'est_recurrent' => false,
                'code' => 'PAQUES',
            ],
            [
                'date' => '2026-05-01',
                'libelle' => 'Fête du Travail',
                'est_recurrent' => true,
                'code' => 'FETE_TRAVAIL',
            ],
            [
                'date' => '2026-05-14',
                'libelle' => 'Ascension',
                'est_recurrent' => false,
                'code' => 'ASCENSION',
            ],
            [
                'date' => '2026-05-25',
                'libelle' => 'Lundi de Pentecôte',
                'est_recurrent' => false,
                'code' => 'PENTECOTE',
            ],
            [
                'date' => '2026-08-07',
                'libelle' => 'Fête de l\'Indépendance',
                'est_recurrent' => true,
                'code' => 'INDEPENDANCE',
            ],
            [
                'date' => '2026-11-15',
                'libelle' => 'Fête de la Paix',
                'est_recurrent' => true,
                'code' => 'PAIX',
            ],
            [
                'date' => '2026-12-25',
                'libelle' => 'Noël',
                'est_recurrent' => true,
                'code' => 'NOEL',
            ],
        ];

        foreach ($feries as $ferie) {
            JourFerie::firstOrCreate(
                ['date' => $ferie['date']],
                array_merge($ferie, [
                    'uuid_jour_ferie' => (string) Str::uuid(),
                ])
            );
        }
    }
}