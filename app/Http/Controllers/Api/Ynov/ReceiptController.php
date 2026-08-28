<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Facture;
use App\Models\Api\Ynov\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    /**
     * Affiche le reçu de paiement
     */
    public function show(string $referenceInterne): View
    {
        // Libellés des types de facture
        $libellesTypeFacture = [
            'N' => 'Prime principale',
            'F' => "Frais d'adhésion",
            'P' => 'Partielle (Reste à payer)',
            'U' => 'Unique',
            'B' => 'Participation aux Bénéfices',
            'E' => 'Exceptionnelle',
            'A' => 'Avance (Remboursement de prêts)',
        ];

        // Libellés des types de paiement
        $libellesType = [
            'firstPayment' => 'Premier paiement',
            'earlyPayment' => 'Paiement anticipé',
            'recoveryPrime' => 'Régularisation de primes',
        ];

        // Récupérer le paiement
        $paiement = Paiement::where('payment_code', $referenceInterne)
            ->orWhere('command_number', $referenceInterne)
            ->firstOrFail();

        // Récupérer les factures associées
        $factures = Facture::where('payment_uuid', $paiement->uuid_paiement)
            ->orderBy('created_at')
            ->get()
            ->map(function ($facture) use ($libellesTypeFacture) {
                $facture->libelleTypeFacture = $libellesTypeFacture[$facture->type_facture] ?? $facture->type_facture;
                return $facture;
            });

        // Identifiant du contrat
        $identifiantContrat = $paiement->id_contrat ?? '';

        // Nom du fichier PDF
        $fileName = 'recu-paiement-' . $paiement->payment_code . '-' . $identifiantContrat . '.pdf';

        Log::info('Affichage reçu', [
            'payment_code' => $paiement->payment_code,
            'fileName' => $fileName
        ]);

        return view('paiement.recu', [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->payment_type] ?? $paiement->payment_type,
            'fileName' => $fileName,
            'identifiantContrat' => $identifiantContrat,
        ]);
    }

    /**
     * Génère et télécharge le PDF du reçu
     */
    public function download(string $referenceInterne)
    {
        $paiement = Paiement::where('payment_code', $referenceInterne)
            ->orWhere('command_number', $referenceInterne)
            ->firstOrFail();

        // Vérifier si le PDF existe déjà
        $identifiantContrat = $paiement->id_contrat ?? '';
        $fileName = 'recu-paiement-' . $paiement->payment_code . '-' . $identifiantContrat . '.pdf';
        $filePath = storage_path('app/public/documents/' . $fileName);

        if (file_exists($filePath)) {
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Générer le PDF si inexistant
        $pdf = $this->generatePdf($paiement);
        return $pdf->download($fileName);
    }

    /**
     * Génère le PDF du reçu
     */
    private function generatePdf(Paiement $paiement)
    {
        $libellesTypeFacture = [
            'N' => 'Prime principale',
            'F' => "Frais d'adhésion",
            'P' => 'Partielle (Reste à payer)',
            'U' => 'Unique',
            'B' => 'Participation aux Bénéfices',
            'E' => 'Exceptionnelle',
            'A' => 'Avance (Remboursement de prêts)',
        ];

        $libellesType = [
            'firstPayment' => 'Premier paiement',
            'earlyPayment' => 'Paiement anticipé',
            'recoveryPrime' => 'Régularisation de primes',
        ];

        $factures = Facture::where('payment_uuid', $paiement->uuid_paiement)
            ->orderBy('created_at')
            ->get()
            ->map(function ($facture) use ($libellesTypeFacture) {
                $facture->libelleTypeFacture = $libellesTypeFacture[$facture->type_facture] ?? $facture->type_facture;
                return $facture;
            });

        $data = [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->payment_type] ?? $paiement->payment_type,
        ];

        $pdf = Pdf::loadView('paiement.recu_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        // Sauvegarder le PDF
        $identifiantContrat = $paiement->id_contrat ?? '';
        $fileName = 'recu-paiement-' . $paiement->payment_code . '-' . $identifiantContrat . '.pdf';
        $filePath = storage_path('app/public/documents/' . $fileName);
        
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        
        file_put_contents($filePath, $pdf->output());

        return $pdf;
    }
}
