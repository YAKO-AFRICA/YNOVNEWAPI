<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropositionController extends Controller
{
    public function storeSouscription(Request $request)
    {
        try {
            //code...
            

            $Variables = [
                'date_de_nassance' => $request->user()->uuid_user,
            ];

            // generation idClient 

            $this->generateIdClient();
            
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    private function generateIdClient()
    {

        //genre 1 masculin 2feminin
        // anne de naissance 2 caratere 2 dernier chiffre
        // moi de naissance 2 caractere
        // numero sequestiel 5 caractere example 00001

    }
}
