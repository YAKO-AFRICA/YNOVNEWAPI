<?php

namespace App\Services;

use Carbon\Carbon;
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

        // $allContrats = [];

        $details = $data['details'][0] ?? null;
        if (!$details) {
            return $this->failure('Aucune information trouvée sur ce contrat.');
        }

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
        $CapitalSouscrit = (float) $data['details'][0]['CapitalSouscrit'];
        $CapitalRente = (float) $data['details'][0]['CapitalRente'];

        // Mettre à jour la variable CapitalSouscrit dans le $data['details'][0] du contrat
        $data['details'][0]['CapitalSouscrit'] = $CapitalSouscrit;

        // Mettre à jour la variable CapitalRente dans le $data['details'][0] du contrat
        $data['details'][0]['CapitalRente'] = $CapitalRente;

        // Mettre le type de la primes en float
        $data['details'][0]['TotalPrime'] = $prime;

        $TotalEmission = (float) $data['details'][0]['TotalEmission'];

        // Mettre à jour la variable TotalEmission dans le $data['details'][0] du contrat
        $data['details'][0]['TotalEmission'] = $TotalEmission;

        $NbreEmission = (int) $data['details'][0]['NbreEmission'];

        // Mettre à jour la variable NbreEmission dans le $data['details'][0] du contrat
        $data['details'][0]['NbreEmission'] = $NbreEmission;

        if ( isset($data['details'][0]['DateNaissance']) && $data['details'][0]['DateNaissance']) {
            // Convertir la date de naissance en date format Y-m-d
            $dateNaissance = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateNaissance'])->format('Y-m-d');
            // Mettre à jour la variable DateNaissance dans le $data['details'][0] du contrat
            $data['details'][0]['DateNaissance'] = $dateNaissance;
        }

        if (isset($data['details'][0]['DateEffetReel']) && $data['details'][0]['DateEffetReel']) {
            // Convertir la date d'effet reel en date format Y-m-d
            $dateEffetReel = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateEffetReel'])->format('Y-m-d');
            
            // Mettre à jour la variable DateEffetReel dans le $data['details'][0] du contrat
            $data['details'][0]['DateEffetReel'] = $dateEffetReel;
        }

        if (isset($data['details'][0]['DateEffetSouhaite']) && $data['details'][0]['DateEffetSouhaite']) {
            // Convertir la date d'effet reel en date format Y-m-d
            $DateEffetSouhaite = Carbon::parse($data['details'][0]['DateEffetSouhaite'])->format('Y-m-d');
            
            // Mettre à jour la variable DateEffetReel dans le $data['details'][0] du contrat
            $data['details'][0]['DateEffetSouhaite'] = $DateEffetSouhaite;
        }
        
        if (isset($data['details'][0]['FinAdhesion']) && $data['details'][0]['FinAdhesion']){
            // Convertir la date de fin d'adhesion en date format Y-m-d
            $FinAdhesion =  Carbon::createFromFormat('d/m/Y', $data['details'][0]['FinAdhesion'])->format('Y-m-d');

            // Mettre à jour la variable FinAdhesion dans le $data['details'][0] du contrat
            $data['details'][0]['FinAdhesion'] = $FinAdhesion;
        }

        $allContrats[] = [
            'IdProposition' => $data['details'][0]['IdProposition'],
            'DateEffet' => $data['details'][0]['DateEffetReel'] ?? $data['details'][0]['DateEffetSouhaite'],
        ];

        foreach ($data['autreContrat'] as $autreContrat) {
            $allContrats[] = [
                'IdProposition' => $autreContrat['IdProposition'],
                'DateEffet' => Carbon::parse($autreContrat['DateEffetReel'] ?? $autreContrat['DateEffetSouhaite'])->format('Y-m-d'),
            ];
        };

        // Log::info($allContrats);
        
        // calculer l'enciennté d'un client en fonction de la det effet du contrat
        
        // Vérifier qu'il existe au moins un contrat
        if (!empty($allContrats)) {

            // Récupérer la date d'effet la plus ancienne
            $premierContrat = collect($allContrats)
                ->filter(fn ($contrat) => !empty($contrat['DateEffet']))
                ->sortBy('DateEffet')
                ->first();

            if ($premierContrat && !empty($premierContrat['DateEffet'])) {

                $datePremiereEffet = Carbon::parse($premierContrat['DateEffet'])->startOfDay();
                $aujourdhui = Carbon::today();

                // Calcul de l'ancienneté complète
                $anciennete = $datePremiereEffet->diff($aujourdhui);

                $ancienneteClient = [
                    'date_premier_contrat' => $datePremiereEffet->format('Y-m-d'),
                    'date_aujourdhui' => $aujourdhui->format('Y-m-d'),
                    'annees' => $anciennete->y,
                    'mois' => $anciennete->m,
                    'jours' => $anciennete->d,
                    'total_mois' => $datePremiereEffet->diffInMonths($aujourdhui),
                    'total_jours' => $datePremiereEffet->diffInDays($aujourdhui),
                ];

                // Log::info('Ancienneté client', $ancienneteClient);
                $data['anciennete'] = $ancienneteClient;
            }

        }
        
        if (!empty($data['error'])) {
            return $this->failure('Contrat introuvable ou en erreur.');
        }

        return [
            'success' => true,
            'message' => 'Contrat trouvée.',
            'data' => $data
        ];
    }


    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => []
        ];
    }

}