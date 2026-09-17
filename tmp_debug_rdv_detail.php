<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rdv = App\Models\Api\Ynov\Rdv::with([
    'client.details',
    'gestionnaire.details',
    'motif',
    'prestation.typePrestation',
    'transmisParUser',
    'agenceSouhaitee',
    'agenceEffective',
    'detailBordereau',
    'detailBordereau.bordereauRdv',
    'contrat',
])->first();

if (!$rdv) {
    echo "NO_RDV\n";
    exit(0);
}

try {
    $out = App\Http\Resources\Api\Ynov\RdvResource::make($rdv)->resolve();
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
