<?php

namespace App\Services\Api\Ynov\Simulateur;

class YapiSimulatorService
{
    public const CODE_PRODUIT = 'YAPI';

    private const PREMIUMS = [
        500000 => 30000,
        1000000 => 58000,
    ];

    public function simulate(array $parameters): array
    {
        $capital = (int) $parameters['Capital'];

        return [
            'codeProduit' => self::CODE_PRODUIT,
            'capital' => $capital,
            'prime' => self::PREMIUMS[$capital],
        ];
    }
}
