<?php

namespace App\Services\Api\Ynov\Simulateur;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class YakoEterniteSimulatorService
{
    public const CODE_PRODUIT = 'yke_2026';

    public const DUREE_COTISATION = 5;

    public const CAPITAUX = [
        500000,
        750000,
        1000000,
        1250000,
        1500000,
        2000000,
        3000000,
        4000000,
        5000000,
    ];

    public const PERIODICITES = ['M', 'T', 'S', 'A'];

    private const FRAIS_ADHESION = 7500;

    private const CAPITAL_SENIOR = 500000;

    public function simulate(array $parameters): array
    {
        $authorization = config('services.yako_tarification.authorization');
        if (! is_string($authorization) || trim($authorization) === '') {
            throw new YakoEterniteTarificationException(
                'La configuration d’authentification du service de tarification est absente.',
                503
            );
        }

        $guarantees = $this->getGuarantees();
        if ($guarantees->isEmpty() || $guarantees->doesntContain(
            fn (ProduitGarantie $guarantee) => (bool) $guarantee->est_obligatoire
        )) {
            throw new YakoEterniteTarificationException(
                'Les garanties Yako Eternité ne sont pas correctement configurées.',
                503
            );
        }

        $birthDate = Carbon::parse($parameters['DateNaissance'])->startOfDay();
        $age = $birthDate->age;
        $seniorSelected = (bool) $parameters['AjouterGarantieSenior'];
        $seniorGuarantee = $guarantees->first(
            fn (ProduitGarantie $guarantee) => strtoupper($guarantee->code_produit_garantie) === 'SENIOR'
        );

        if ($seniorSelected && $seniorGuarantee === null) {
            throw new YakoEterniteTarificationException(
                'La garantie Senior Yako Eternité n’est pas configurée.',
                503
            );
        }

        $guaranteeData = [
            'garantieObligatoire' => [],
            'garantieOptionnel' => [],
        ];

        foreach ($guarantees as $guarantee) {
            $isSenior = strtoupper($guarantee->code_produit_garantie) === 'SENIOR';

            if ($isSenior && $seniorSelected) {
                $premium = (float) $parameters['PrimeSenior'];
                $capital = self::CAPITAL_SENIOR;
            } else {
                [$premium, $capital] = $this->calculateGuaranteePremium(
                    $parameters,
                    $guarantee,
                    $age
                );
            }

            $key = $guarantee->est_obligatoire
                ? 'garantieObligatoire'
                : 'garantieOptionnel';
            $guaranteeData[$key][] = [
                'uuid_garantie' => $guarantee->uuid_produit_garantie,
                'codeGarantie' => $guarantee->code_produit_garantie,
                'libelle' => $guarantee->libelle,
                'capital' => $capital,
                'prime' => round($premium, 2),
            ];
        }

        $allGuarantees = array_merge(
            $guaranteeData['garantieObligatoire'],
            $guaranteeData['garantieOptionnel']
        );
        $mainPremium = round(array_sum(array_column($allGuarantees, 'prime')), 2);

        return [
            'garantieData' => $guaranteeData,
            'infoSimulation' => [
                'isAssure' => $parameters['IsAssure'],
                'primeFinal' => round($mainPremium + self::FRAIS_ADHESION, 2),
                'primepricipale' => $mainPremium,
                'codeProduit' => self::CODE_PRODUIT,
                'periodicite' => $parameters['CodePeriodicite'],
                'duree' => self::DUREE_COTISATION,
                'age' => $age,
                'capital' => (int) $parameters['Capital'],
                'dateEffet' => $parameters['DateEffet'],
                'datenaissance' => $parameters['DateNaissance'],
                'fraisAdhesion' => self::FRAIS_ADHESION,
            ],
        ];
    }

    protected function getGuarantees(): Collection
    {
        return ProduitGarantie::query()
            ->whereRaw('LOWER(code_produit) = ?', [self::CODE_PRODUIT])
            ->where('branche', 'IND')
            ->orderByRaw('CASE WHEN est_obligatoire = 1 THEN 0 ELSE 1 END')
            ->orderBy('code_produit_garantie')
            ->get();
    }

    private function calculateGuaranteePremium(
        array $parameters,
        ProduitGarantie $guarantee,
        int $age
    ): array {
        $response = $this->post('prime-garantie', [
            'CodeProduit' => self::CODE_PRODUIT,
            'codePeriodicite' => $parameters['CodePeriodicite'],
            'duree' => self::DUREE_COTISATION,
            'age' => $age,
            'capitalSouscrit' => (int) $parameters['Capital'],
            'dateEffet' => $parameters['DateEffet'],
            'dateNaissance' => $parameters['DateNaissance'],
            'codeGarantie' => $guarantee->code_produit_garantie,
        ]);

        $record = $response[0] ?? null;
        $premium = is_array($record) ? ($record['Prime'] ?? null) : null;
        $capital = is_array($record) ? ($record['Capital'] ?? $parameters['Capital']) : null;
        if (
            ! is_numeric($premium)
            || (float) $premium < 0
            || ! is_numeric($capital)
            || (float) $capital <= 0
        ) {
            throw new YakoEterniteTarificationException(
                "La prime calculée pour la garantie {$guarantee->code_produit_garantie} est invalide."
            );
        }

        return [round((float) $premium, 2), (int) $capital];
    }

    private function post(string $endpoint, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.yako_tarification.base_url'), '/');
        if ($baseUrl === '') {
            throw new YakoEterniteTarificationException(
                'L’URL du service de tarification n’est pas configurée.',
                503
            );
        }

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->withHeaders([
                    'Authorization' => config('services.yako_tarification.authorization'),
                ])
                ->timeout(20)
                ->post("{$baseUrl}/{$endpoint}", $payload);
        } catch (ConnectionException $exception) {
            throw new YakoEterniteTarificationException(
                "Impossible de joindre le service de tarification ({$endpoint}).",
                502,
                $exception
            );
        }

        if (! $response->successful()) {
            throw new YakoEterniteTarificationException(
                "Le service de tarification a répondu avec le statut {$response->status()} ({$endpoint})."
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new YakoEterniteTarificationException(
                "Le service de tarification a retourné une réponse JSON invalide ({$endpoint})."
            );
        }

        if (! empty($data['error'])) {
            throw new YakoEterniteTarificationException(
                "Le service de tarification a signalé une erreur ({$endpoint})."
            );
        }

        return $data;
    }
}
