<?php

namespace App\Services;

use App\Models\Api\Ynov\parameter\User;
use App\Models\Contrat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EncaissementBisService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.api.encaissement_bis');
    }

    public function getContrat($idcontrat)
    {
        if (!$idcontrat) {
            return $this->failure('Aucun id contrat fourni.');
        }

        try {
            $response = Http::timeout(60)->post($this->endpoint, ['idContrat' => $idcontrat]);
        } catch (\Throwable $e) {
            Log::error('Erreur appel encaissement-bis', ['idContrat' => $idcontrat, 'message' => $e->getMessage()]);
            return $this->failure('Service de recuperation des informations du contrat indisponible.');
        }

        if (!$response->successful()) {
            Log::warning('Réponse encaissement-bis non successful', [
                'idContrat' => $idcontrat, 
                'status' => $response->status()
            ]);
            return $this->failure('Impossible de recuperer les informations de ce contrat pour le moment.');
        }

        $data = $response->json();

        // Recupérer le nombre de primes encaissées
        $NbrencConfirmer = count($data['enc']['confirmer']);

        // Recupérer le montant total des primes encaissées
        $TotalEncaissement = array_sum(array_map(function ($item) {
            return isset($item['RegltMontant']) ? (float) $item['RegltMontant'] : 0;
        }, $data['enc']['confirmer']));

        // Mettre à jour la variable TotalEncaissement dans le $data['details'][0] du contrat
        $data['details'][0]['NbreEncaissment'] = $NbrencConfirmer;

        // Mettre à jour la variable TotalEncaissement dans le $data['details'][0] du contrat
        $data['details'][0]['TotalEncaissement'] = $TotalEncaissement;

         // Recupérer le nombre de primes impayées ou en attente
         $NbreImpayes = count($data['enc']['nonRegle']);

        // Recupérer le montant total des primes impayées ou en attente
        $TotalImpayes = array_sum(array_map(function ($item) {
            return isset($item['MontantNet']) ? (float) $item['MontantNet'] : 0;
        }, $data['enc']['nonRegle']));

        // Mettre à jour la variable NbreImpayes dans le $data['details'][0] du contrat
        $data['details'][0]['NbreImpayes'] = $NbreImpayes;

        // Mettre à jour la variable TotalImpayes dans le $data['details'][0] du contrat
        $data['details'][0]['TotalImpayes'] = $TotalImpayes;

        // Mettre le type de la primes en float
        $prime = (float) $data['details'][0]['TotalPrime'];

        // Mettre le type de la primes en float
        $data['details'][0]['TotalPrime'] = $prime;

        if (!empty($data['error'])) {
            return $this->failure('Contrat introuvable ou en erreur.');
        }

        $details = $data['details'][0] ?? null;
        if (!$details) {
            return $this->failure('Aucune information trouvée sur ce contrat.');
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }


    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

}