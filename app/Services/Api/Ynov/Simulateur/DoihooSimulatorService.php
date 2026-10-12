<?php

namespace App\Services\Api\Ynov\Simulateur;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoihooSimulatorService
{
    public const CODE_PRODUIT = 'DOIHOO';

    public const DUREE_CONTRAT = 8;

    private const FRAIS_ADHESION = 7500;

    public function simulate(array $parameters): array
    {
        $authorization = config('services.yako_tarification.authorization');

        if (! is_string($authorization) || trim($authorization) === '') {
            throw new DoihooTarificationException(
                'La configuration d’authentification du service de tarification est absente.',
                503
            );
        }

        $capital = (float) $parameters['Capital'];
        $age = (int) 99;
        $duration = (int) $parameters['Duree'];
        $productCode = $parameters['CodeProduit'];
        $periodicity = $parameters['CodePeriodicite'];

        $tableResponse = $this->post('get-table-prime-web', [
            'CodeProduit' => $productCode,
            'CodePeriodicite' => $periodicity,
        ]);
        $premiumTables = $tableResponse['dataTablePrime'] ?? null;
        if (! is_array($premiumTables) || $premiumTables === []) {
            throw new DoihooTarificationException(
                'Aucune table de tarification Doihoo n’a été retournée.'
            );
        }

        $guarantees = $this->getGuarantees();

        if ($guarantees->isEmpty()) {
            throw new DoihooTarificationException(
                'Aucune garantie Doihoo individuelle n’est configurée.'
            );
        }

        $guaranteeData = [];
        foreach ($guarantees as $guarantee) {
            $premiumTable = collect($premiumTables)->first(
                fn ($table) => is_array($table)
                    && ($table['CodeProduitGarantie'] ?? null) === $guarantee->code_produit_garantie
            );

            if (! is_array($premiumTable)) {
                throw new DoihooTarificationException(
                    "Aucune table de tarification n’a été trouvée pour la garantie {$guarantee->code_produit_garantie}."
                );
            }

            $groupCode = $premiumTable['CodeGRoupeIntervalle'] ?? null;

            Log::info("Calculating premium for guarantee {$guarantee->code_produit_garantie} with group code: " . json_encode($groupCode) . " and table code: " . json_encode($premiumTable['codeTable'] ?? null));

            $tableCode = $premiumTable['codeTable'] ?? null;
            if (
                (! is_string($groupCode) && ! is_int($groupCode))
                || (string) $groupCode === ''
                || (! is_string($tableCode) && ! is_int($tableCode))
                || (string) $tableCode === ''
            ) {
                throw new DoihooTarificationException(
                    "La table de tarification de la garantie {$guarantee->code_produit_garantie} est incomplète."
                );
            }

            $premiumResponse = $this->post('get-prime-by-param-web', [
                'CodeGroupe' => (string) $groupCode,
                'AgeAssure' => $age,
                'Capital' => $capital,
                'codeTable' => (string) $tableCode,
                'Duree' => $duration,
            ]);

            $premium = $premiumResponse['dataTablePrimeRes'][0]['Prime'] ?? null;
            if (! is_numeric($premium) || (float) $premium < 0) {
                throw new DoihooTarificationException(
                    "La prime calculée pour la garantie {$guarantee->code_produit_garantie} est invalide."
                );
            }

            $guaranteeData[] = [
                'codeGarantie' => $guarantee->code_produit_garantie,
                'libelle' => $guarantee->libelle,
                'capital' => $capital,
                'prime' => round((float) $premium, 2),
            ];
        }

        $totalPremium = round(array_sum(array_column($guaranteeData, 'prime')), 2);

        return [
            'garantieData' => $guaranteeData,
            'infoSimulation' => [
                'codeProduit' => $productCode,
                'periodicite' => $periodicity,
                'capital' => $capital,
                'primepricipale' => $totalPremium,
                'age' => $age,
                'duree' => $duration,
                'fraisAdhesion' => self::FRAIS_ADHESION,
                'primeFinale' => round($totalPremium + self::FRAIS_ADHESION, 2),
            ],
        ];
    }

    protected function getGuarantees(): Collection
    {
        return ProduitGarantie::query()
            ->where('code_produit', 'DOIHOO')
            ->where('branche', 'IND')
            ->orderByRaw("CASE WHEN nature_garantie = 'Principale Obligatoire' THEN 0 ELSE 1 END")
            ->orderBy('code_produit_garantie')
            ->get();
    }

    private function post(string $endpoint, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.yako_tarification.base_url'), '/');
        if ($baseUrl === '') {
            throw new DoihooTarificationException(
                'L’URL du service de tarification n’est pas configurée.',
                503
            );
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => config('services.yako_tarification.authorization'),
                ])
                ->timeout(20)
                ->post("{$baseUrl}/{$endpoint}", $payload);
        } catch (ConnectionException $exception) {
            throw new DoihooTarificationException(
                "Impossible de joindre le service de tarification ({$endpoint}).",
                502,
                $exception
            );
        }

        if (! $response->successful()) {
            throw new DoihooTarificationException(
                "Le service de tarification a répondu avec le statut {$response->status()} ({$endpoint})."
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new DoihooTarificationException(
                "Le service de tarification a retourné une réponse JSON invalide ({$endpoint}, HTTP {$response->status()})."
            );
        }

        if (! empty($data['error'])) {
            $providerMessage = $data['message']
                ?? (is_array($data['error']) ? ($data['error']['message'] ?? null) : $data['error']);
            $providerMessage = is_string($providerMessage)
                ? mb_substr(trim(strip_tags($providerMessage)), 0, 300)
                : null;
            $details = $providerMessage !== null && $providerMessage !== ''
                ? ": {$providerMessage}"
                : '.';

            throw new DoihooTarificationException(
                "Le service de tarification a signalé une erreur ({$endpoint}, HTTP {$response->status()}){$details}"
            );
        }

        return $data;
    }
}
