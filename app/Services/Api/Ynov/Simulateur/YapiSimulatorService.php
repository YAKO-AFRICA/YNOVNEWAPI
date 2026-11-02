<?php

namespace App\Services\Api\Ynov\Simulateur;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use Illuminate\Database\Eloquent\Collection;

class YapiSimulatorService
{
    public const CODE_PRODUIT = 'LFFUN';

    private const CODE_PRODUIT_GARANTIE = 'LFFUN';

    private const FRAIS_ADHESION = 7500;
    private const DUREE = 1;

    private const PREMIUMS = [
        500000 => 30000,
        1000000 => 58000,
    ];

    public function simulate(array $parameters): array
    {
        $capital = (int) $parameters['Capital'];
        $premium = self::PREMIUMS[$capital];
        $guarantees = $this->getGuarantees();
        $mainGuarantee = $guarantees->first(
            fn (ProduitGarantie $guarantee) => $guarantee->est_obligatoire
        );

        if ($mainGuarantee === null) {
            throw new YapiSimulatorException(
                'La garantie obligatoire Yapi n’est pas configurée.'
            );
        }

        $guaranteeData = [
            'garantieObligatoire' => [],
            'garantieOptionnel' => [],
        ];

        foreach ($guarantees as $guarantee) {
            $isMandatory = (bool) $guarantee->est_obligatoire;
            $guaranteeData[$isMandatory ? 'garantieObligatoire' : 'garantieOptionnel'][] = [
                'uuid_garantie' => $guarantee->uuid_produit_garantie,
                'codeGarantie' => $guarantee->code_produit_garantie,
                'libelle' => $guarantee->libelle,
                'capital' => $capital,
                'prime' => $guarantee->is($mainGuarantee) ? $premium : 0,
            ];
        }

        return [
            'garantieData' => $guaranteeData,
            'infoSimulation' => [
                'codeProduit' => self::CODE_PRODUIT,
                'capital' => $capital,
                'duree' => self::DUREE,
                'primepricipale' => $premium,
                'fraisAdhesion' => self::FRAIS_ADHESION,
                'primeFinale' => round($premium + self::FRAIS_ADHESION, 2),
            ],
        ];
    }

    protected function getGuarantees(): Collection
    {
        return ProduitGarantie::query()
            ->where('code_produit', self::CODE_PRODUIT_GARANTIE)
            ->where('branche', 'COL')
            ->orderByRaw('CASE WHEN est_obligatoire = 1 THEN 0 ELSE 1 END')
            ->orderBy('code_produit_garantie')
            ->get();
    }
}
