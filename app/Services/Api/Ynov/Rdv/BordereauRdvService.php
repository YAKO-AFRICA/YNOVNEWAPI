<?php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\BordereauRdv;
use App\Models\Api\Ynov\DetailBordereauRdv;
use App\Models\Api\Ynov\Rdv;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BordereauRdvService
{
    /**
     * Garantit qu'un RDV transmis appartient à un bordereau de la bonne période.
     * La période est calculée à partir de date_transmission.
    */
    public function ensureForRdv(Rdv $rdv): BordereauRdv
    {
        if (!$rdv->date_transmission) {
            $rdv->update([
                'date_transmission' => now(),
            ]);
        }

        $dateTransmission = Carbon::parse($rdv->date_transmission);
        [$periode1, $periode2] = $this->resolvePeriodFromTransmission($dateTransmission);

        return DB::transaction(function () use ($rdv, $periode1, $periode2) {
            $lot = BordereauRdv::whereDate('periode_1', $periode1->toDateString())
                ->whereDate('periode_2', $periode2->toDateString())
                ->first();

            if (!$lot) {
                $lot = BordereauRdv::create([
                    'reference' => $this->generateReference($periode1, $periode2),
                    'periode_1' => $periode1->toDateString(),
                    'periode_2' => $periode2->toDateString(),
                    'status' => $this->computeLotStatus($periode1, $periode2),
                    'observation' => null,
                    'created_by' => $rdv->created_by ?? null,
                    'updated_by' => $rdv->updated_by ?? null,
                ]);
            }

            $this->syncLotStatus($lot);

            $detailExists = DetailBordereauRdv::where('rdv_uuid', $rdv->uuid_rdvs)
                ->where('bordereau_rdv_uuid', $lot->uuid_bordereau_rdv)
                ->exists();

            if (!$detailExists) {
                DetailBordereauRdv::create([
                    'uuid_detail_bordereau_rdv' => (string) Str::uuid(),
                    'bordereau_rdv_uuid' => $lot->uuid_bordereau_rdv,
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'status' => 'en_attente',
                    'created_by' => $rdv->created_by ?? null,
                    'observation' => null,
                ]);
            }

            return $lot->fresh();
        });
    }

    /**
     * Calcule la période métier à partir de date_transmission.
     * Règle : semaine lundi->dimanche, lot1 lundi->jeudi, lot2 vendredi->dimanche.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriodFromTransmission(Carbon $date): array
    {
        //
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);
        $lot1End = $startOfWeek->copy()->addDays(3);

        if ($date->lte($lot1End)) {
            return [$startOfWeek, $lot1End];
        }

        return [$lot1End->copy()->addDay()->startOfDay(), $endOfWeek];
    }

    // Synchronisation du statut du lot en fonction de la période et de la date actuelle
    protected function computeLotStatus(Carbon $periode1, Carbon $periode2): string
    {
        $today = now()->startOfDay();

        if ($today->between(
            $periode1->copy()->startOfDay(),
            $periode2->copy()->endOfDay()
        )) {
            return 'transfere';
        }

        return 'en_attente';
    }


    // Synchronisation du statut du lot en fonction de la période et de la date actuelle
    protected function syncLotStatus(BordereauRdv $lot): void
    {
        $periode1 = Carbon::parse($lot->periode_1)->startOfDay();
        $periode2 = Carbon::parse($lot->periode_2)->endOfDay();
        $today = now()->startOfDay();

        if ($lot->status === 'en_attente' && $today->between($periode1, $periode2)) {
            $lot->update([
                'status' => 'transfere',
                'updated_by' => $lot->created_by ?? null,
            ]);
        }
    }

    /**
     * Génère une référence unique pour le bordereau.
     * Format : BR-YYYY-SWW-XXXXXXXX
     */
    protected function generateReference(Carbon $periode1, Carbon $periode2): string
    {
        $year = $periode1->year;
        $week = $periode1->weekOfYear;

        return sprintf(
            'BR-%s-S%02d-%s',
            $year,
            $week,
            strtoupper(substr(md5(uniqid((string) microtime(true), true)), 0, 8))
        );
    }
}
