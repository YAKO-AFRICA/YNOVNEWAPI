<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

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

        $TotalEncaissementPartielle = array_sum(array_map(function ($item) {
            return isset($item['MontantNet']) ? (float) $item['MontantNet'] : 0;
        }, $data['enc']['partielle'] ?? []));

        $NbrencPartielle = count($data['enc']['partielle'] ?? []);



        // Recupérer le montant total des primes encaissées
        $TotalEncaissement = array_sum(array_map(function ($item) {
            return isset($item['RegltMontant']) ? (float) $item['RegltMontant'] : 0;
        }, $data['enc']['confirmer'] ?? []));

        // Mettre à jour les variables
        $data['details'][0]['NbreEncaissment'] = $NbrencConfirmer;
        $data['details'][0]['TotalEncaissement'] = $TotalEncaissement;

        $data['details'][0]['NbrencPartielle'] = $NbrencPartielle;
        $data['details'][0]['TotalEncaissementPartielle'] = $TotalEncaissementPartielle;
        


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
            'CP' => [],
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
                    foreach ($avtFiles as $i => $avtFile) {
                        $avtFileName = basename($avtFile);
                        $avtFileUrl = url('get-document-contrat/' . $filePath . '/' . $avtFileName);
                        $avtFileUrls[] = [
                            'libelle' => 'Avenant de police d\'assurance n° ' . ($i + 1),
                            'fileName' => $avtFileName,
                            'docUrl' => $avtFileUrl
                        ];
                    }
                }

                $data['documents'] = [
                    'CP' => [
                        'libelle' => 'Police d\'assurance (Conditions particulières et générales)',
                        'fileName' => $fileName,
                        'docUrl' => $fileUrl
                    ],
                    'avenantsUrls' => $avtFileUrls,
                ];
            } else {
                // Aucun fichier trouvé
                $data['documents'] = [
                    'CP' => [],
                    'avenantsUrls' => [],
                    'message' => 'Documents non disponibles'
                ];
                
                // Log::warning('Aucun document trouvé pour le contrat', [
                //     'contrat_id' => $idcontrat,
                //     'cp_path' => $CPfilePath,
                //     'cg_path' => $CGProdFilePath
                // ]);
            }

        } catch (\Exception $e) {
            // Log::error('Erreur génération PDF', [
            //     'contrat_id' => $idcontrat,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            
            $data['documents'] = [
                'CP' => [],
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


    public function verifierContrat(string $idContrat, string $paymentType): array
    {
        try {
            $response = Http::timeout(60)->post($this->endpoint, ['idContrat' => $idContrat]);
        } catch (\Throwable $e) {
            
            return $this->failure('Service de vérification du contrat indisponible.');
        }

        if (!$response->successful()) {
            
            return $this->failure('Impossible de vérifier ce contrat pour le moment.');
        }

        $data = $response->json();

        if (!empty($data['error'])) {
            return $this->failure('Contrat introuvable ou en erreur.');
        }

        $details = $data['details'][0] ?? null;
        if (!$details) {
            return $this->failure('Aucune information trouvée pour ce contrat.');
        }

        if (($details['OnStdbyOff'] ?? '') == "3") {
            return $this->failure('Ce contrat est arrêté. Impossible d\'effectuer un paiement !');
        }

        $assures = $data['assures'] ?? [];
        $fraisAdhesion = 0;
        // foreach ($assures as $garantie) {
        //     $fraisAdhesion += (int) round((float) ($garantie['FraisAcces'] ?? 0));
        // }

        $primePrincipale = (int) round((float) ($details['TotalPrime'] ?? 0));

        $nonRegle = $data['enc']['nonRegle'] ?? [];
        $facturesImpayees = array_map(static function (array $f) {
            return [
                'IdPresentation' => (string) ($f['IdPresentation'] ?? ''),
                'CodePresentation' => $f['CodePresentation'] ?? '',
                'MaDate' => $f['MaDate'] ?? null,
                'TypePresentation' => $f['TypePresentation'] ?? null,
                'MontantNet' => (int) round((float) ($f['MontantNet'] ?? 0)),
            ];
        }, $nonRegle);

        return [
            'success' => true,
            'idProposition' => $details['IdProposition'] ?? null,
            'codeProposition' => $details['CodeProposition'] ?? null,
            'souscripteur' => trim(($details['nomSous'] ?? '') . ' ' . ($details['PrenomSous'] ?? '')),
            'numSouscripteur' => $details['CodeProposant'] ?? null,
            'primePrincipale' => $primePrincipale,
            'fraisAdhesion' => $fraisAdhesion,
            'devise' => 'XOF',
            'aDesImpayes' => count($facturesImpayees) > 0,
            'facturesImpayees' => $facturesImpayees,
            'codeConseiller' => $details['CodeConseiller'] ?? null,
            'codeProduit' => $details['codeProduit'] ?? null,
            'produit' => $details['produit'] ?? null,
        ];
    }

    /**
     * Récupère les détails d'un contrat Web
     */
    public function recupDetailsContratWeb(string $idContrat, string $paymentType): array
    {
        try {
                // $contrat = Contrat::where('id', $idContrat)->first();

                // if (!$contrat) {
                //     Log::warning('Contrat non trouvé', ['idContrat' => $idContrat]);
                //     return $this->failure('Impossible de récupérer les détails du contrat.');
                // }

                // return [
                //     'success' => true,
                //     'contratIdWeb' => $contrat->id ?? null,
                //     'primePrincipale' => (int) ($contrat->primepricipale ?? 0),
                //     'fraisAdhesion' => (int) ($contrat->fraisadhesion ?? 0),
                //     'devise' => 'XOF',
                //     'codeProduit' => $contrat->codeproduit ?? null,
                //     'produit' => $contrat->libelleproduit ?? null,
                // ];

                return [
                    'success' => true,
                    'contratIdWeb' => $idContrat,
                    'primePrincipale' => 0,
                    'fraisAdhesion' => 0,
                    'devise' => 'XOF',
                    'codeProduit' => $paymentType,
                    'produit' => '',
                ];
                
        } catch (\Throwable $e) {
            Log::error('Erreur récupération contrat', [
                'idContrat' => $idContrat,
                'message' => $e->getMessage()
            ]);
            return $this->failure('Service de vérification du contrat indisponible.');
        }
    }

    /**
     * Recalcule le total des factures impayées sélectionnées
     *
     * @param string[] $selectedInvoiceIds
     */
    public function recalculerTotalImpayes(string $idContrat, array $selectedInvoiceIds, string $paymentType): array
    {
        $contrat = $this->verifierContrat($idContrat, $paymentType);
        if (!$contrat['success']) {
            return $this->failure($contrat['message'] ?? 'Contrat invalide.');
        }

        $selected = array_filter(
            $contrat['facturesImpayees'],
            static fn (array $f) => in_array($f['IdPresentation'], $selectedInvoiceIds, true)
        );

        if (count($selected) === 0) {
            return $this->failure('Aucune facture sélectionnée valide pour ce contrat.');
        }

        $total = array_sum(array_column($selected, 'MontantNet'));

        return [
            'success' => true,
            'contrat' => $contrat,
            'facturesSelectionnees' => array_values($selected),
            'totalCents' => $total,
        ];
    }

    /**
     * Retourne une réponse d'erreur
     */
    private function failure(string $message): array
    {
        return [
            'success' => false,
            'code' => 'CONTRACT_NOT_FOUND',
            'message' => $message,
            'aDesImpayes' => false,
            'facturesImpayees' => [],
            'primePrincipale' => 0,
            'fraisAdhesion' => 0,
            'data' => []
        ];
    }
}

