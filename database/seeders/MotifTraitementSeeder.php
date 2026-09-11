<?php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\MotifTraitement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MotifTraitementSeeder extends Seeder
{
    /**
     * Seeder des motifs de traitement utilisés par :
     * - E-RDV
     * - E-PRESTATION
     * - E-SINISTRE
     *
     * Le type est automatiquement déterminé à partir du libellé.
     */
    public function run(): void
    {
        $motifs = [

            /*
            |--------------------------------------------------------------------------
            | E-RDV / E-PRESTATION
            |--------------------------------------------------------------------------
            */
            [
                'libelle' => 'Besoin urgent de liquidités',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Changement de situation personnelle (mariage, naissance, divorce)',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Changement de situation professionnelle (retraite, perte d’emploi, expatriation)',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Impayés / incapacité à poursuivre les cotisations',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Le produit souscrit n’est pas conforme à mes attentes',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Souscription à une offre concurrente plus attractive',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Transformation vers un autre contrat',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Terme du contrat signifié par le commercial',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Convenances personnelles',
                'module' => ['E-RDV', 'E-PRESTATION'],
            ],
            [
                'libelle' => 'Autres (Veuillez préciser dans les commentaires)',
                'module' => ['E-RDV', 'E-PRESTATION', 'E-SINISTRE'],
            ],

            /*
            |--------------------------------------------------------------------------
            | E-PRESTATION
            |--------------------------------------------------------------------------
            */
            [
                'libelle' => 'Numéro de paiement par Mobile Money non identifié',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'RIB du client illisible',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'RIB du client non joint à la demande de prestation',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'CNI du client illisible',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'CNI du client non jointe à la demande de prestation',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'Contrat non joint à la demande de prestation (bulletin, condition particulière ou déclaration de perte)',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'Objet de la prestation incorrect',
                'module' => ['E-PRESTATION'],
            ],
            [
                'libelle' => 'Formulaire de demande de prestation non daté, non signé ou non précédé de la mention « Lu et approuvé »',
                'module' => ['E-PRESTATION'],
            ],
            [
                'libelle' => 'Nom et prénom du gestionnaire ou manager ayant reçu le client manquants',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],
            [
                'libelle' => 'Le manager ayant omis de transmettre le dossier dans le délai convenu',
                'module' => ['E-PRESTATION', 'E-SINISTRE'],
            ],

            /*
            |--------------------------------------------------------------------------
            | E-SINISTRE
            |--------------------------------------------------------------------------
            */
            [
                'libelle' => 'Déclaration de sinistre',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Transmission du dossier',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Vérification documentaire',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'En attente de pièces justificatives',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Erreur sur le montant à payer',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Erreur sur la prime',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Dossier incomplet',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Demande d’information complémentaire',
                'module' => ['E-SINISTRE'],
            ],
            [
                'libelle' => 'Refus de traitement',
                'module' => ['E-SINISTRE'],
            ],
        ];

        foreach ($motifs as $motif) {
            $this->createOrUpdateMotif(
                $motif['libelle'],
                $motif['module'] ?? []
            );
        }
    }

    /**
     * Création ou mise à jour d'un motif.
     *
     * Si le motif existe déjà, les modules sont fusionnés
     * afin d'éviter de créer plusieurs lignes pour le même libellé.
     */
    private function createOrUpdateMotif(string $libelle, array $modules): void
    {
        $libelle = $this->cleanLabel($libelle);

        $modules = collect($modules)
            ->map(fn ($module) => strtoupper(trim($module)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($modules)) {
            $modules = ['E-PRESTATION'];
        }

        $motif = MotifTraitement::query()
            ->where('libelle', $libelle)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Motif existant
        |--------------------------------------------------------------------------
        */
        if ($motif) {

            $existingModules = is_array($motif->module)
                ? $motif->module
                : [];

            $mergedModules = collect([
                ...$existingModules,
                ...$modules,
            ])
                ->map(fn ($module) => strtoupper(trim($module)))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $motif->update([
                'type' => $this->inferType($libelle),
                'module' => $mergedModules,
                'status' => $motif->status ?: 'actif',
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Nouveau motif
        |--------------------------------------------------------------------------
        */
        MotifTraitement::query()->create([
            'uuid_motif_traitements' => (string) Str::uuid(),
            'libelle' => $libelle,
            'type' => $this->inferType($libelle),
            'status' => 'actif',
            'module' => $modules,
        ]);
    }

    /**
     * Détermine automatiquement le type du motif.
     *
     * Types possibles :
     * - annulation
     * - correction
     * - report
     * - validation
     * - informations
     * - demande
     * - autre
     */
    private function inferType(string $libelle): array
    {
        $normalized = $this->normalize($libelle);

        /*
        |--------------------------------------------------------------------------
        | 1. ANNULATION
        |--------------------------------------------------------------------------
        */
        if ($this->containsAny($normalized, [
            'annul',
            'cancel',
            'supprim',
            'retir',
            'revoq',
        ])) {
            return ['annulation'];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CORRECTION
        |--------------------------------------------------------------------------
        */
        if ($this->containsAny($normalized, [
            'rej',
            'refus',
            'non conforme',
            'impossible',
            'incoh',
            'incorrect',
            'erreur',
            'corrig',
            'modifier',
            'modification',
            'changer',
            'manqu',
            'illisible',
            'non identif',
            'non joint',
            'incomplet',
            'mauvais',
            'erron',
            'omet',
            'omission',
        ])) {
            return ['correction'];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. REPORT
        |--------------------------------------------------------------------------
        */
        if ($this->containsAny($normalized, [
            'report',
            'reporter',
            'defer',
            'differe',
            'retard',
            'retarde',
        ])) {
            return ['report'];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. VALIDATION
        |--------------------------------------------------------------------------
        */
        if ($this->containsAny($normalized, [
            'verif',
            'confirm',
            'transmission',
            'transmettre',
            'validation',
            'valider',
            'declaration',
            'declare',
        ])) {
            return ['validation'];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. INFORMATIONS
        |--------------------------------------------------------------------------
        */
        if ($this->containsAny($normalized, [
            'attend',
            'delai',
            'cycle',
            'rib',
            'facture',
            'contrat',
            'document',
            'piece',
            'fiche',
            'renseigner',
            'dossier',
            'information',
            'justificatif',
        ])) {
            return ['informations'];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. DEMANDE
        |--------------------------------------------------------------------------
        */
        if ($this->containsAny($normalized, [
            'demande',
            'besoin',
            'convenance',
            'situation',
            'enquete',
            'autre',
        ])) {
            return ['demande'];
        }

        return ['autre'];
    }

    /**
     * Vérifie si une chaîne contient au moins un des mots/expressions.
     */
    private function containsAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($value, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nettoyage du libellé.
     */
    private function cleanLabel(string $value): string
    {
        $value = trim($value);

        // Remplacement des espaces multiples
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Normalisation :
     * - minuscules
     * - suppression des accents
     * - apostrophes typographiques
     * - espaces multiples
     */
    private function normalize(string $value): string
    {
        $value = trim($value);

        // Apostrophes typographiques
        $value = str_replace(
            ['’', '‘', '`'],
            "'",
            $value
        );

        // Minuscules avec support UTF-8
        $value = mb_strtolower($value, 'UTF-8');

        // Suppression des accents
        $value = strtr($value, [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ã' => 'a',

            'ç' => 'c',

            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',

            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',

            'ñ' => 'n',

            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'õ' => 'o',

            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',

            'ý' => 'y',
            'ÿ' => 'y',

            'œ' => 'oe',
            'æ' => 'ae',
        ]);

        // Nettoyage des espaces
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}

