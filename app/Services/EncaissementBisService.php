<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

// class EncaissementBisService
// {
//     protected string $endpoint;

//     public function __construct()
//     {
//         $this->endpoint = config('services.api.encaissement_bis');
//     }

//     public function getContrat($idcontrat)
//     {
//         if (!$idcontrat) {
//             return $this->failure('Aucun id contrat fourni.');
//         }

//         $externalUploadDir = base_path(env('GET_CUSTOMER_CP'));
//         $externalCGPRODDir = base_path(env('GET_DOC_CGPROD'));

//         $avtFileUrls = [];

//         try {
//             $response = Http::timeout(60)->post($this->endpoint, ['idContrat' => $idcontrat]);
//         } catch (\Throwable $e) {
//             Log::error('Erreur appel encaissement-bis', ['idContrat' => $idcontrat, 'message' => $e->getMessage()]);
//             return $this->failure('Service de recuperation des informations du contrat indisponible.');
//         }

//         if (!$response->successful()) {
//             Log::warning('Réponse encaissement-bis non successful', [
//                 'idContrat' => $idcontrat, 
//                 'status' => $response->status()
//             ]);
//             return $this->failure('Impossible de recuperer les informations de ce contrat pour le moment.');
//         }

//         $data = $response->json();

//         $details = $data['details'][0] ?? null;
//         if (!$details) {
//             return $this->failure('Aucune information trouvée sur ce contrat.');
//         }
        
//         if (!in_array($data['details'][0]['CodeCanalDistribution'], ['INDIV', 'AGTGLE', 'SAAF'])) {
//             return $this->failure('Ce contrat n\'est pas pris en charge par YNOV.');
//         }

//         // Recupérer le nombre de primes encaissées
//         $NbrencConfirmer = count($data['enc']['confirmer']);

//         // Recupérer le montant total des primes encaissées
//         $TotalEncaissement = array_sum(array_map(function ($item) {
//             return isset($item['RegltMontant']) ? (float) $item['RegltMontant'] : 0;
//         }, $data['enc']['confirmer']));

//         // Mettre à jour la variable TotalEncaissement dans le $data['details'][0] du contrat
//         $data['details'][0]['NbreEncaissment'] = $NbrencConfirmer;

//         // Mettre à jour la variable TotalEncaissement dans le $data['details'][0] du contrat
//         $data['details'][0]['TotalEncaissement'] = $TotalEncaissement;

//          // Recupérer le nombre de primes impayées ou en attente
//          $NbreImpayes = count($data['enc']['nonRegle']);

//         // Recupérer le montant total des primes impayées ou en attente
//         $TotalImpayes = array_sum(array_map(function ($item) {
//             return isset($item['MontantNet']) ? (float) $item['MontantNet'] : 0;
//         }, $data['enc']['nonRegle']));

//         // Mettre à jour la variable NbreImpayes dans le $data['details'][0] du contrat
//         $data['details'][0]['NbreImpayes'] = $NbreImpayes;

//         // Mettre à jour la variable TotalImpayes dans le $data['details'][0] du contrat
//         $data['details'][0]['TotalImpayes'] = $TotalImpayes;

//         // Mettre le type de la primes en float
//         $prime = (float) $data['details'][0]['TotalPrime'];
//         $CapitalSouscrit = (float) $data['details'][0]['CapitalSouscrit'];
//         $CapitalRente = (float) $data['details'][0]['CapitalRente'];
//         $DureeCotisationAns = (float) $data['details'][0]['DureeCotisationAns'];

//         // Mettre à jour la variable CapitalSouscrit dans le $data['details'][0] du contrat
//         $data['details'][0]['CapitalSouscrit'] = $CapitalSouscrit ?? $CapitalRente;

//         // Mettre à jour la variable CapitalRente dans le $data['details'][0] du contrat
//         $data['details'][0]['CapitalRente'] = $CapitalRente;

//         // Mettre à jour la variable DureeCotisationAns dans le $data['details'][0] du contrat
//         $data['details'][0]['DureeCotisationAns'] = $DureeCotisationAns;

//         // Mettre le type de la primes en float
//         $data['details'][0]['TotalPrime'] = $prime;

//         $TotalEmission = (float) $data['details'][0]['TotalEmission'];

//         // Mettre à jour la variable TotalEmission dans le $data['details'][0] du contrat
//         $data['details'][0]['TotalEmission'] = $TotalEmission;

//         $NbreEmission = (int) $data['details'][0]['NbreEmission'];

//         // Mettre à jour la variable NbreEmission dans le $data['details'][0] du contrat
//         $data['details'][0]['NbreEmission'] = $NbreEmission;

//         if ( isset($data['details'][0]['DateNaissance']) && $data['details'][0]['DateNaissance']) {
//             // Convertir la date de naissance en date format Y-m-d
//             $dateNaissance = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateNaissance'])->format('Y-m-d');
//             // Mettre à jour la variable DateNaissance dans le $data['details'][0] du contrat
//             $data['details'][0]['DateNaissance'] = $dateNaissance;
//         }

//         if (isset($data['details'][0]['DateEffetReel']) && $data['details'][0]['DateEffetReel']) {
//             // Convertir la date d'effet reel en date format Y-m-d
//             $dateEffetReel = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateEffetReel'])->format('Y-m-d');
            
//             // Mettre à jour la variable DateEffetReel dans le $data['details'][0] du contrat
//             $data['details'][0]['DateEffetReel'] = $dateEffetReel;
//         }

//         if (isset($data['details'][0]['DateEffetSouhaite']) && $data['details'][0]['DateEffetSouhaite']) {
//             // Convertir la date d'effet reel en date format Y-m-d
//             $DateEffetSouhaite = Carbon::parse($data['details'][0]['DateEffetSouhaite'])->format('Y-m-d');
            
//             // Mettre à jour la variable DateEffetReel dans le $data['details'][0] du contrat
//             $data['details'][0]['DateEffetSouhaite'] = $DateEffetSouhaite;
//         }
        
//         if (isset($data['details'][0]['FinAdhesion']) && $data['details'][0]['FinAdhesion']){
//             // Convertir la date de fin d'adhesion en date format Y-m-d
//             $FinAdhesion =  Carbon::createFromFormat('d/m/Y', $data['details'][0]['FinAdhesion'])->format('Y-m-d');

//             // Mettre à jour la variable FinAdhesion dans le $data['details'][0] du contrat
//             $data['details'][0]['FinAdhesion'] = $FinAdhesion;
//         }

//         $allContrats[] = [
//             'IdProposition' => $data['details'][0]['IdProposition'],
//             'DateEffet' => $data['details'][0]['DateEffetReel'] ?? $data['details'][0]['DateEffetSouhaite'],
//         ];

//         foreach ($data['autreContrat'] as $autreContrat) {
//             $allContrats[] = [
//                 'IdProposition' => $autreContrat['IdProposition'],
//                 'DateEffet' => Carbon::parse($autreContrat['DateEffetReel'] ?? $autreContrat['DateEffetSouhaite'])->format('Y-m-d'),
//             ];
//         };

//         $DureeCotisationMois = $DureeCotisationAns * 12;
        

//         switch ($data['details'][0]['periodicite']) {
//             case "M":
//                 $Duree = $DureeCotisationMois;
//                 break;
//             case "T":
//                 $Duree = $DureeCotisationMois / 3; // Trimestriel = tous les 3 mois
//                 break;
//             case "S":
//                 $Duree = $DureeCotisationMois / 6; // Semestriel = tous les 6 mois
//                 break;
//             case "A":
//                 $Duree = $DureeCotisationMois / 12; // Annuel = tous les 12 mois
//                 break;
//             case "U":
//                 $Duree = $NbrencConfirmer; // Annuel = tous les 12 mois
//                 break;
//             default:
//                 $Duree = 0; // Gérer les cas non définis
//                 break;
//         }

//         $data['details'][0]['DureeCotisationMois'] = $Duree;

//         // calculer l'etat actuel d'avancement des cotisations du contrat (En %)
//         $EtatAvancement = ($NbrencConfirmer / $data['details'][0]['DureeCotisationMois']) * 100;
//         $data['details'][0]['EtatAvancementCotisation'] = round($EtatAvancement, 2);

//         // Log::info($allContrats);
        
//         // calculer l'enciennté d'un client en fonction de la det effet du contrat
        
//         // Vérifier qu'il existe au moins un contrat
//         if (!empty($allContrats)) {

//             // Récupérer la date d'effet la plus ancienne
//             $premierContrat = collect($allContrats)
//                 ->filter(fn ($contrat) => !empty($contrat['DateEffet']))
//                 ->sortBy('DateEffet')
//                 ->first();

//             if ($premierContrat && !empty($premierContrat['DateEffet'])) {

//                 $datePremiereEffet = Carbon::parse($premierContrat['DateEffet'])->startOfDay();
//                 $aujourdhui = Carbon::today();

//                 // Calcul de l'ancienneté complète
//                 $anciennete = $datePremiereEffet->diff($aujourdhui);

//                 $ancienneteClient = [
//                     'date_premier_contrat' => $datePremiereEffet->format('Y-m-d'),
//                     'date_aujourdhui' => $aujourdhui->format('Y-m-d'),
//                     'annees' => $anciennete->y,
//                     'mois' => $anciennete->m,
//                     'jours' => $anciennete->d,
//                     'total_mois' => $datePremiereEffet->diffInMonths($aujourdhui),
//                     'total_jours' => $datePremiereEffet->diffInDays($aujourdhui),
//                 ];

//                 // Log::info('Ancienneté client', $ancienneteClient);
//                 $data['anciennete'] = $ancienneteClient;
//             }

//         }

//         $garanties = collect($data['assures'] ?? [])
//         ->unique('CodeGarantie')
//         ->values()
//         ->map(function ($garantie) {
//             return [
//                 'CodeGarantie' => $garantie['CodeGarantie'] ?? null,
//                 'Libelle' => $garantie['MonLibelle'] ?? null,
//                 'Capital' => (float) $garantie['Capital'] ?? 0,
//                 'Prime' => (float) $garantie['Prime'] ?? 0,
//                 'PrimePrincipale' => $garantie['PrimePrincipale'] ?? 0,
//                 'DateEffet' => !empty($garantie['DateEffet'])
//                     ? $garantie['DateEffet']
//                     : null,
//                 'DateEcheance' => !empty($garantie['DateEcheance'])
//                     ? $garantie['DateEcheance']
//                     : null,
//                 'DureeCouvAns' => (int) $garantie['DureeCouvAns'] ?? null,
//                 'DureePrimeAns' => (float) $garantie['DureePrimeAns'] ?? null,
//                 'Periodicite' => $garantie['CodePerodicite'] ?? null,
//             ];
//         })
//         ->toArray();

//         $data['garanties'] = $garanties;


//         // recuperer l'annee de la date d'effet reelle
//         $annee = Carbon::parse($data['details'][0]['DateEffetReel'])->format('Y');

//         // recuperer le mois de la date d'effet reelle en format de deux chiffres et retirer le zéro si le mois est inferieur a 10
//         $mois =  Carbon::parse($data['details'][0]['DateEffetReel'])->format('m');
//         $mois = ltrim($mois, '0');
        
//         $CPfileName = "A{$annee}/M{$mois}/CP_{$idcontrat}.pdf";
//         $CPfilePath = $externalUploadDir  . DIRECTORY_SEPARATOR . $CPfileName;

//         $CGProdFile = "CG_{$data['details'][0]['codeProduit']}.pdf";
//         $CGProdFilePath = $externalCGPRODDir  . DIRECTORY_SEPARATOR . $CGProdFile;
//         // Initialiser FPDI pour fusionner les fichiers CPfilePath et CGProdFile 
//         $finalPdf = new Fpdi();

//         // Ajouter toutes les pages du bulletin
//         $CPPageCount = $finalPdf->setSourceFile($CPfilePath);
//         for ($pageNo = 1; $pageNo <= $CPPageCount; $pageNo++) {
//             $finalPdf->AddPage();
//             $tplIdx = $finalPdf->importPage($pageNo);
//             $finalPdf->useTemplate($tplIdx);
//         }
    
//         // Ajouter toutes les pages du fichier CGU
//         $CGProdPageCount = $finalPdf->setSourceFile($CGProdFilePath);
//         for ($pageNo = 1; $pageNo <= $CGProdPageCount; $pageNo++) {
//             $finalPdf->AddPage();
//             $tplIdx = $finalPdf->importPage($pageNo);
//             $finalPdf->useTemplate($tplIdx);
//         }
        
//         // Nom du fichier final
//         $FilePath = "A{$annee}/M{$mois}/DocumentsContractuels_{$idcontrat}";
//         if (!is_dir($externalUploadDir . DIRECTORY_SEPARATOR . $FilePath)) {
//             mkdir($externalUploadDir . DIRECTORY_SEPARATOR . $FilePath, 0777, true);
//         }
//         $fileName = "CP-CG_{$idcontrat}.pdf";
//         $finalFilePath = $externalUploadDir  . DIRECTORY_SEPARATOR . $FilePath . DIRECTORY_SEPARATOR . $fileName;
//         $finalPdf->Output($finalFilePath, 'F');

//         // recuperer tous les fichiers commençant par AVT_{$idcontrat} present dans le dossier $FilePath
//         $avtFiles = glob($externalUploadDir . DIRECTORY_SEPARATOR . $FilePath . DIRECTORY_SEPARATOR . 'AVT_' . $idcontrat . '*.pdf');

//         if (!empty($avtFiles)) {
//             foreach ($avtFiles as $avtFile) {
//                 $avtFileName = basename($avtFile);
//                 $avtFileUrl = url('get-document-contrat/' . $FilePath . DIRECTORY_SEPARATOR . $avtFileName);
//                 $avtFileUrls[] = $avtFileUrl;
//             }
//         }

//         // Construire l'URL absolue du fichier PDF
//         $fileUrl = url('get-document-contrat/' . $FilePath . DIRECTORY_SEPARATOR . $fileName);

//         $data['documents'] = [
//             'CP' => $fileUrl,
//             'avenantsUrls' => $avtFileUrls,
//         ];

//         if (!empty($data['error'])) {
//             return $this->failure('Contrat introuvable ou en erreur.');
//         }

//         return [
//             'success' => true,
//             'code' => 'CONTRACT_FOUND',
//             'message' => 'Contrat trouvée.',
//             'data' => $data
//         ];
//     }


//     private function failure(string $message): array
//     {
//         return [
//             'success' => false,
//             'code' => 'CONTRACT_NOT_FOUND',
//             'message' => $message,
//             'data' => []
//         ];
//     }



// }

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

        $externalUploadDir = base_path(env('GET_CUSTOMER_CP'));
        $externalCGPRODDir = base_path(env('GET_DOC_CGPROD'));

        $avtFileUrls = [];

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

        $details = $data['details'][0] ?? null;
        if (!$details) {
            return $this->failure('Aucune information trouvée sur ce contrat.');
        }
        
        // Vérifier le canal de distribution
        $canalDistribution = $data['details'][0]['CodeCanalDistribution'] ?? null;
        if (!in_array($canalDistribution, ['INDIV', 'AGTGLE', 'SAAF'])) {
            return $this->failure('Ce contrat n\'est pas pris en charge par YNOV.');
        }

        // Recupérer le nombre de primes encaissées
        $NbrencConfirmer = count($data['enc']['confirmer'] ?? []);

        // Recupérer le montant total des primes encaissées
        $TotalEncaissement = array_sum(array_map(function ($item) {
            return isset($item['RegltMontant']) ? (float) $item['RegltMontant'] : 0;
        }, $data['enc']['confirmer'] ?? []));

        // Mettre à jour les variables
        $data['details'][0]['NbreEncaissment'] = $NbrencConfirmer;
        $data['details'][0]['TotalEncaissement'] = $TotalEncaissement;

        // Recupérer le nombre de primes impayées ou en attente
        $NbreImpayes = count($data['enc']['nonRegle'] ?? []);

        // Recupérer le montant total des primes impayées ou en attente
        $TotalImpayes = array_sum(array_map(function ($item) {
            return isset($item['MontantNet']) ? (float) $item['MontantNet'] : 0;
        }, $data['enc']['nonRegle'] ?? []));

        $data['details'][0]['NbreImpayes'] = $NbreImpayes;
        $data['details'][0]['TotalImpayes'] = $TotalImpayes;

        // Mettre le type de la primes en float
        $prime = (float) ($data['details'][0]['TotalPrime'] ?? 0);
        $CapitalSouscrit = (float) ($data['details'][0]['CapitalSouscrit'] ?? 0);
        $CapitalRente = (float) ($data['details'][0]['CapitalRente'] ?? 0);
        $DureeCotisationAns = (float) ($data['details'][0]['DureeCotisationAns'] ?? 0);

        $data['details'][0]['CapitalSouscrit'] = $CapitalSouscrit ?? $CapitalRente;
        $data['details'][0]['CapitalRente'] = $CapitalRente;
        $data['details'][0]['DureeCotisationAns'] = $DureeCotisationAns;
        $data['details'][0]['TotalPrime'] = $prime;

        $TotalEmission = (float) ($data['details'][0]['TotalEmission'] ?? 0);
        $data['details'][0]['TotalEmission'] = $TotalEmission;

        $NbreEmission = (int) ($data['details'][0]['NbreEmission'] ?? 0);
        $data['details'][0]['NbreEmission'] = $NbreEmission;

        // Conversion des dates avec gestion d'erreurs
        if (isset($data['details'][0]['DateNaissance']) && $data['details'][0]['DateNaissance']) {
            try {
                $dateNaissance = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateNaissance'])->format('Y-m-d');
                $data['details'][0]['DateNaissance'] = $dateNaissance;
            } catch (\Exception $e) {
                Log::warning('Conversion date naissance échouée', ['date' => $data['details'][0]['DateNaissance']]);
            }
        }

        if (isset($data['details'][0]['DateEffetReel']) && $data['details'][0]['DateEffetReel']) {
            try {
                $dateEffetReel = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateEffetReel'])->format('Y-m-d');
                $data['details'][0]['DateEffetReel'] = $dateEffetReel;
            } catch (\Exception $e) {
                Log::warning('Conversion date effet reel échouée', ['date' => $data['details'][0]['DateEffetReel']]);
            }
        }

        if (isset($data['details'][0]['DateEffetSouhaite']) && $data['details'][0]['DateEffetSouhaite']) {
            try {
                $DateEffetSouhaite = Carbon::parse($data['details'][0]['DateEffetSouhaite'])->format('Y-m-d');
                $data['details'][0]['DateEffetSouhaite'] = $DateEffetSouhaite;
            } catch (\Exception $e) {
                Log::warning('Conversion date effet souhaite échouée', ['date' => $data['details'][0]['DateEffetSouhaite']]);
            }
        }
        
        if (isset($data['details'][0]['FinAdhesion']) && $data['details'][0]['FinAdhesion']) {
            try {
                $FinAdhesion = Carbon::createFromFormat('d/m/Y', $data['details'][0]['FinAdhesion'])->format('Y-m-d');
                $data['details'][0]['FinAdhesion'] = $FinAdhesion;
            } catch (\Exception $e) {
                Log::warning('Conversion fin adhesion échouée', ['date' => $data['details'][0]['FinAdhesion']]);
            }
        }

        $allContrats = [];
        
        // Ajouter le contrat principal
        $allContrats[] = [
            'IdProposition' => $data['details'][0]['IdProposition'] ?? null,
            'DateEffet' => $data['details'][0]['DateEffetReel'] ?? $data['details'][0]['DateEffetSouhaite'] ?? null,
        ];

        // Ajouter les autres contrats
        foreach ($data['autreContrat'] ?? [] as $autreContrat) {
            $allContrats[] = [
                'IdProposition' => $autreContrat['IdProposition'] ?? null,
                'DateEffet' => $autreContrat['DateEffetReel'] ?? $autreContrat['DateEffetSouhaite'] ?? null,
            ];
        }

        $DureeCotisationMois = $DureeCotisationAns * 12;
        
        $periodicite = $data['details'][0]['periodicite'] ?? null;
        
        switch ($periodicite) {
            case "M":
                $Duree = $DureeCotisationMois;
                break;
            case "T":
                $Duree = $DureeCotisationMois / 3;
                break;
            case "S":
                $Duree = $DureeCotisationMois / 6;
                break;
            case "A":
                $Duree = $DureeCotisationMois / 12;
                break;
            case "U":
                $Duree = $NbrencConfirmer;
                break;
            default:
                $Duree = 0;
                break;
        }

        $data['details'][0]['DureeCotisationMois'] = $Duree;

        // Calcul de l'état d'avancement des cotisations
        $EtatAvancement = $Duree > 0 ? ($NbrencConfirmer / $Duree) * 100 : 0;
        $data['details'][0]['EtatAvancementCotisation'] = round($EtatAvancement, 2);

        // Calculer l'ancienneté du client
        if (!empty($allContrats)) {
            $premierContrat = collect($allContrats)
                ->filter(fn ($contrat) => !empty($contrat['DateEffet']))
                ->sortBy('DateEffet')
                ->first();

            if ($premierContrat && !empty($premierContrat['DateEffet'])) {
                $datePremiereEffet = Carbon::parse($premierContrat['DateEffet'])->startOfDay();
                $aujourdhui = Carbon::today();

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

                $data['anciennete'] = $ancienneteClient;
            }
        }

        // Traitement des garanties
        $garanties = collect($data['assures'] ?? [])
            ->unique('CodeGarantie')
            ->values()
            ->map(function ($garantie) {
                return [
                    'CodeGarantie' => $garantie['CodeGarantie'] ?? null,
                    'Libelle' => $garantie['MonLibelle'] ?? null,
                    'Capital' => (float) ($garantie['Capital'] ?? 0),
                    'Prime' => (float) ($garantie['Prime'] ?? 0),
                    'PrimePrincipale' => $garantie['PrimePrincipale'] ?? 0,
                    'DateEffet' => $garantie['DateEffet'] ?? null,
                    'DateEcheance' => $garantie['DateEcheance'] ?? null,
                    'DureeCouvAns' => (int) ($garantie['DureeCouvAns'] ?? 0),
                    'DureePrimeAns' => (float) ($garantie['DureePrimeAns'] ?? 0),
                    'Periodicite' => $garantie['CodePerodicite'] ?? null,
                ];
            })
            ->toArray();

        $data['garanties'] = $garanties;

        // ============================================================
        // GESTION DES DOCUMENTS PDF AVEC VÉRIFICATION DES FICHIERS
        // ============================================================
        $data['documents'] = [
            'CP' => null,
            'avenantsUrls' => [],
        ];

        try {
            // Récupérer la date d'effet pour construire le chemin
            $dateEffetReel = $data['details'][0]['DateEffetReel'] ?? null;
            
            if ($dateEffetReel) {
                try {
                    $dateEffet = Carbon::parse($dateEffetReel);
                    $annee = $dateEffet->format('Y');
                    $mois = ltrim($dateEffet->format('m'), '0');
                } catch (\Exception $e) {
                    Log::warning('Erreur parsing date effet reel', ['date' => $dateEffetReel]);
                    $annee = date('Y');
                    $mois = ltrim(date('m'), '0');
                }
            } else {
                $annee = date('Y');
                $mois = ltrim(date('m'), '0');
            }
            
            $CPfileName = "A{$annee}/M{$mois}/CP_{$idcontrat}.pdf";
            $CPfilePath = $externalUploadDir . DIRECTORY_SEPARATOR . $CPfileName;

            $codeProduit = $data['details'][0]['codeProduit'] ?? 'default';
            $CGProdFile = "CG_{$codeProduit}.pdf";
            $CGProdFilePath = $externalCGPRODDir . DIRECTORY_SEPARATOR . $CGProdFile;

            // Vérifier l'existence des fichiers
            $cpExists = file_exists($CPfilePath);
            $cgExists = file_exists($CGProdFilePath);

            Log::info('Vérification fichiers PDF', [
                'contrat_id' => $idcontrat,
                'cp_path' => $CPfilePath,
                'cp_exists' => $cpExists,
                'cg_path' => $CGProdFilePath,
                'cg_exists' => $cgExists
            ]);

            // Si au moins un fichier existe, générer le PDF fusionné
            if ($cpExists || $cgExists) {
                $finalPdf = new Fpdi();

                // Ajouter le bulletin (CP)
                if ($cpExists) {
                    try {
                        $CPPageCount = $finalPdf->setSourceFile($CPfilePath);
                        for ($pageNo = 1; $pageNo <= $CPPageCount; $pageNo++) {
                            $finalPdf->AddPage();
                            $tplIdx = $finalPdf->importPage($pageNo);
                            $finalPdf->useTemplate($tplIdx);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erreur ajout CP au PDF', [
                            'contrat_id' => $idcontrat,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Ajouter le fichier CGU
                if ($cgExists) {
                    try {
                        $CGProdPageCount = $finalPdf->setSourceFile($CGProdFilePath);
                        for ($pageNo = 1; $pageNo <= $CGProdPageCount; $pageNo++) {
                            $finalPdf->AddPage();
                            $tplIdx = $finalPdf->importPage($pageNo);
                            $finalPdf->useTemplate($tplIdx);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erreur ajout CG au PDF', [
                            'contrat_id' => $idcontrat,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Nom du fichier final
                $filePath = "A{$annee}/M{$mois}/DocumentsContractuels_{$idcontrat}";
                $fullDirPath = $externalUploadDir . DIRECTORY_SEPARATOR . $filePath;
                
                if (!is_dir($fullDirPath)) {
                    mkdir($fullDirPath, 0777, true);
                }
                
                $fileName = "CP-CG_{$idcontrat}.pdf";
                $finalFilePath = $fullDirPath . DIRECTORY_SEPARATOR . $fileName;
                $finalPdf->Output($finalFilePath, 'F');

                // Construire l'URL
                $fileUrl = url('get-document-contrat/' . $filePath . '/' . $fileName);
                
                // Récupérer les fichiers AVT
                $avtFiles = glob($fullDirPath . DIRECTORY_SEPARATOR . 'AVT_' . $idcontrat . '*.pdf');

                if (!empty($avtFiles)) {
                    foreach ($avtFiles as $avtFile) {
                        $avtFileName = basename($avtFile);
                        $avtFileUrl = url('get-document-contrat/' . $filePath . '/' . $avtFileName);
                        $avtFileUrls[] = $avtFileUrl;
                    }
                }

                $data['documents'] = [
                    'CP' => $fileUrl,
                    'avenantsUrls' => $avtFileUrls,
                ];
            } else {
                // Aucun fichier trouvé
                $data['documents'] = [
                    'CP' => null,
                    'avenantsUrls' => [],
                    'message' => 'Documents non disponibles'
                ];
                
                Log::warning('Aucun document trouvé pour le contrat', [
                    'contrat_id' => $idcontrat,
                    'cp_path' => $CPfilePath,
                    'cg_path' => $CGProdFilePath
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur génération PDF', [
                'contrat_id' => $idcontrat,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $data['documents'] = [
                'CP' => null,
                'avenantsUrls' => [],
                'error' => 'Impossible de générer les documents: ' . $e->getMessage()
            ];
        }

        if (!empty($data['error'])) {
            return $this->failure('Contrat introuvable ou en erreur.');
        }

        return [
            'success' => true,
            'code' => 'CONTRACT_FOUND',
            'message' => 'Contrat trouvée.',
            'data' => $data
        ];
    }

    private function failure(string $message): array
    {
        return [
            'success' => false,
            'code' => 'CONTRACT_NOT_FOUND',
            'message' => $message,
            'data' => []
        ];
    }
}