<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

/**
 * Adapter WebDAV qui bufferise les uploads streamés en mémoire
 * et bascule sur l'upload chunké Nextcloud (dav/uploads) quand le
 * serveur rejette le PUT avec une erreur 413 (payload too large).
 *
 * Sabre/Guzzle échoue sur les PUT avec body stream non-rewindable
 * ("necessary data rewind wasn't possible") avec certains serveurs
 * Nextcloud derrière proxy. On lit le stream puis on délègue à write().
 */
class BufferedWebDavAdapter extends WebDAVAdapter
{
    private const CHUNK_SIZE = 8 * 1024 * 1024; // 8 Mo par chunk

    public function __construct(
        Client $client,
        string $prefix = '',
        string $visibilityHandling = self::ON_VISIBILITY_THROW_ERROR,
        bool $manualCopy = false,
        bool $manualMove = false,
        private array $httpConfig = [],
    ) {
        parent::__construct($client, $prefix, $visibilityHandling, $manualCopy, $manualMove);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $data = stream_get_contents($contents);

        if ($data === false) {
            $data = '';
        }

        $this->write($path, $data, $config);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            parent::write($path, $contents, $config);
        } catch (UnableToWriteFile $e) {
            if (!str_contains($e->getMessage(), '413') || !$this->chunkedUpload($path, $contents)) {
                throw $e;
            }
        }
    }

    /**
     * Upload chunké Nextcloud (API dav/uploads v2) :
     * MKCOL du dossier de transfert (avec Destination), PUT des chunks
     * nommés 00001, 00002..., puis MOVE de ".file" vers la destination.
     * L'endpoint uploads/ n'est pas soumis à la limite de 1 Mo de /files/.
     */
    private function chunkedUpload(string $path, string $contents): bool
    {
        $baseUri  = $this->httpConfig['base_uri'] ?? null;
        $userName = $this->httpConfig['user_name'] ?? null;
        $password = $this->httpConfig['password'] ?? null;
        $prefix   = $this->httpConfig['prefix'] ?? '';

        if (!$baseUri || !$userName) {
            return false;
        }

        // .../remote.php/dav/files/{user}/ -> .../remote.php/dav/uploads/{user}/
        $filesBase   = rtrim($baseUri, '/') . '/';
        $uploadsBase = preg_replace('#/files/[^/]+/$#', '/uploads/' . $userName . '/', $filesBase);

        if (!$uploadsBase || $uploadsBase === $filesBase) {
            return false;
        }

        $destination = $filesBase
            . ($prefix !== '' ? trim($prefix, '/') . '/' : '')
            . implode('/', array_map('rawurlencode', explode('/', $path)));

        $auth = fn () => Http::withBasicAuth($userName, (string) $password)->timeout(300);

        $transferId  = 't' . bin2hex(random_bytes(8));
        $transferUrl = $uploadsBase . $transferId;
        $total       = strlen($contents);
        $headers     = [
            'Destination'     => $destination,
            'OC-Total-Length' => (string) $total,
        ];

        // 1. Créer le dossier de transfert
        $mkcol = $auth()->withHeaders($headers)->send('MKCOL', $transferUrl);

        if (!in_array($mkcol->status(), [201, 301, 302, 405], true)) {
            return false;
        }

        // 2. Envoyer les chunks (numérotation sur 5 chiffres, à partir de 1)
        $chunkNumber = 1;
        $offset      = 0;

        while ($offset < $total) {
            $chunk = substr($contents, $offset, self::CHUNK_SIZE);

            $put = $auth()
                ->withBody($chunk, 'application/octet-stream')
                ->withHeaders($headers)
                ->put($transferUrl . '/' . str_pad((string) $chunkNumber, 5, '0', STR_PAD_LEFT));

            if (!in_array($put->status(), [200, 201, 204], true)) {
                $this->abortChunkedUpload($transferUrl);
                return false;
            }

            $offset      += strlen($chunk);
            $chunkNumber++;
        }

        // 3. Assemblage : MOVE de ".file" vers la destination
        $move = $auth()
            ->withHeaders($headers)
            ->send('MOVE', $transferUrl . '/.file');

        if (!in_array($move->status(), [200, 201, 204], true)) {
            $this->abortChunkedUpload($transferUrl);
            return false;
        }

        return true;
    }

    /**
     * Annule un upload chunké en cours (DELETE du dossier de transfert).
     */
    private function abortChunkedUpload(string $transferUrl): void
    {
        try {
            Http::withBasicAuth(
                $this->httpConfig['user_name'] ?? '',
                (string) ($this->httpConfig['password'] ?? '')
            )->timeout(60)->send('DELETE', $transferUrl . '/');
        } catch (\Throwable) {
            // Ignorer : le dossier expire de toute façon après 24 h
        }
    }
}
