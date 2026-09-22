<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\ClientNumberGenerator;
use DateTimeImmutable;
use Illuminate\Http\Request;

class PropositionController extends Controller
{

    public function __construct(
        private ClientNumberGenerator $clientNumberGenerator
    ) {}
    public function storeSouscription(Request $request)
    {
        try {

            // generation idClient 
            $numeroClient = $this->clientNumberGenerator->generate(
                'M',
                new DateTimeImmutable('2000-01-01'),
            );
            // $numeroClient = $this->clientNumberGenerator->generate(
            //     (int) $adherentData['genre'],
            //     $adherentData['date_naissance']
            // );

            return $numeroClient;

            

            
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
