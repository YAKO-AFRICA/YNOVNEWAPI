<?php

namespace App\Console\Commands;

use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\Rdv\TraitementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireRdvs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdvs:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire les rendez-vous qui n\'ont pas eu lieu le jour-J et passe en rejeté après 3 jours';

    /**
     * Execute the console command.
     */
    public function handle(TraitementService $traitementService): int
    {
        $this->info('Début de l\'expiration des rendez-vous...');

        // RDV à passer en status 'expire' (le jour même si pas de réception)
        $rdvsToExpire = Rdv::where('status', 'en_attente')
            ->whereDate('date_rdv_souhaiter', '<', Carbon::today())
            ->whereNotIn('status', ['annule', 'rejete', 'traite', 'expire'])
            ->get();

        $expiredCount = 0;
        foreach ($rdvsToExpire as $rdv) {
            $result = $traitementService->expirer(
                $rdv,
                [
                    'motif_expiration' => 'Expiration automatique - Client non présent',
                    'observation' => 'Le rendez-vous n\'a pas eu lieu le jour prévu.',
                ],
                'system'
            );

            if ($result['success']) {
                $expiredCount++;
                $this->line("RDV {$rdv->code} passé en status 'expire'");
            }
        }

        // RDV à passer en status 'rejete' (3 jours après expiration)
        $rdvsToReject = Rdv::where('status', 'expire')
            ->whereDate('date_rdv_souhaiter', '<', Carbon::today()->subDays(3))
            ->get();

        $rejectedCount = 0;
        foreach ($rdvsToReject as $rdv) {
            $result = $traitementService->rejeter(
                $rdv,
                [
                    'motif_rejet' => 'Rejet automatique - Expiration de 3 jours',
                    'observation' => 'Le rendez-vous a expiré depuis plus de 3 jours sans action.',
                ],
                'system'
            );

            if ($result['success']) {
                $rejectedCount++;
                $this->line("RDV {$rdv->code} passé en status 'rejete'");
            }
        }

        $this->info('Expiration terminée.');
        $this->info("RDV expirés: {$expiredCount}");
        $this->info("RDV rejetés: {$rejectedCount}");

        return Command::SUCCESS;
    }
}
