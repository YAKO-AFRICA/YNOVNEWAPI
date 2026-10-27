<?php

use App\Models\Api\Ynov\SignatureRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Chiffre signature_requests.api_key (cast 'encrypted' du modèle).
 * - élargit la colonne en TEXT (la valeur chiffrée est bien plus longue que la clé)
 * - chiffre les lignes existantes encore en clair
 *
 * À lancer AVANT de déployer le nouveau modèle (sinon la lecture des anciennes lignes échoue).
 */
return new class extends Migration
{
    private function connection()
    {
        return DB::connection((new SignatureRequest())->getConnectionName());
    }

    public function up(): void
    {
        $db = $this->connection();

        $db->statement('ALTER TABLE signature_requests MODIFY api_key TEXT NULL');

        $db->table('signature_requests')
            ->whereNotNull('api_key')
            ->get(['uuid_signature_request', 'api_key'])
            ->each(function ($row) use ($db) {
                try {
                    Crypt::decryptString($row->api_key); // déjà chiffrée : on ne touche pas
                    return;
                } catch (DecryptException $e) {
                    // en clair : on chiffre
                }

                $db->table('signature_requests')
                    ->where('uuid_signature_request', $row->uuid_signature_request)
                    ->update(['api_key' => Crypt::encryptString($row->api_key)]);
            });
    }

    public function down(): void
    {
        $db = $this->connection();

        $db->table('signature_requests')
            ->whereNotNull('api_key')
            ->get(['uuid_signature_request', 'api_key'])
            ->each(function ($row) use ($db) {
                try {
                    $plain = Crypt::decryptString($row->api_key);
                } catch (DecryptException $e) {
                    return;
                }

                $db->table('signature_requests')
                    ->where('uuid_signature_request', $row->uuid_signature_request)
                    ->update(['api_key' => $plain]);
            });
    }
};

/*
 * =====================================================================
 * config/services.php  — bloc à ajouter / remplacer
 * =====================================================================
 *
 * 'signature' => [
 *     'api_webhook_key'        => env('SIGNATURE_API_WEBHOOK_KEY'),
 *     // Hôtes autorisés pour les documents (proxy) — OBLIGATOIRE en production
 *     'allowed_document_hosts' => array_filter(array_map('trim', explode(',', env('SIGNATURE_ALLOWED_DOCUMENT_HOSTS', '')))),
 *     // Hôtes autorisés pour les webhooks des apps hôtes (facultatif mais recommandé)
 *     'allowed_webhook_hosts'  => array_filter(array_map('trim', explode(',', env('SIGNATURE_ALLOWED_WEBHOOK_HOSTS', '')))),
 * ],
 *
 * .env :
 * SIGNATURE_ALLOWED_DOCUMENT_HOSTS=docs.yako-africa.ci,storage.yako-africa.ci
 * SIGNATURE_ALLOWED_WEBHOOK_HOSTS=app-hote.yako-africa.ci
 *
 * CACHE_DRIVER doit être partagé entre processus (redis, database ou file), pas "array".
 */
