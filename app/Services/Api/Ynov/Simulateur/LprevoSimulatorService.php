<?php

namespace App\Services\Api\Ynov\Simulateur;

use App\Models\Api\Ynov\parameter\ProduitGarantie;

class LprevoSimulatorService
{
    public const CODE_PRODUIT = 'LPREVO';

    private const CAPITALS = [
        100000 => 1000,
        250000 => 2500,
        500000 => 5000,
    ];

    private const FRAIS_ADHESION = 5500;

    private const PRIME_PAR_PATHOLOGIE = 5500;

    public const PATHOLOGIES = [
        'Diabète',
        'AVC',
        'Cancer',
        'Insuffisance Rénale',
        'Hypertension',
    ];

    public function simulate(array $parameters): array
    {
        $capital = (int) $parameters['Capital'];
        $bonneSante = (bool) $parameters['BonneSante'];
        $pathologies = $bonneSante ? [] : array_values(array_unique($parameters['Pathologies']));
        $primePrincipale = self::CAPITALS[$capital];
        $primePathologies = count($pathologies) * self::PRIME_PAR_PATHOLOGIE;
        $primeFinale = $primePrincipale + $primePathologies + self::FRAIS_ADHESION;

        $guarantee = $this->getMainGuarantee();
        if ($guarantee === null) {
            throw new LprevoSimulatorException(
                'La garantie obligatoire LPREVO n’est pas configurée.'
            );
        }

        return [
            'garantieData' => [[
                'codeGarantie' => $guarantee->code_produit_garantie,
                'prime' => $primePrincipale,
                'capital' => $capital,
                'libelle' => $guarantee->libelle,
            ]],
            'pathologies' => $pathologies,
            'infoSimulation' => [
                'isAssure' => 'oui',
                'primeFinal' => $primeFinale,
                'primepricipale' => $primePrincipale,
                'primePathologies' => $primePathologies,
                'codeProduit' => self::CODE_PRODUIT,
                'periodicite' => 'A',
                'duree' => 1,
                'surprime' => $primePathologies,
                'capital' => $capital,
                'fraisadhesion' => self::FRAIS_ADHESION,
                'bonneSante' => $bonneSante,
                'pathologies' => $pathologies,
            ],
        ];
    }

    protected function getMainGuarantee(): ?ProduitGarantie
    {
        return ProduitGarantie::query()
            ->where('code_produit', self::CODE_PRODUIT)
            ->where('branche', 'IND')
            ->where('code_produit_garantie', 'DTC/IAD')
            ->where('est_obligatoire', true)
            ->first();
    }
}
