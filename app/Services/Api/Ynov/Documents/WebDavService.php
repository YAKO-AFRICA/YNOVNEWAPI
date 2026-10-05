<?php

namespace App\Services\Api\Ynov\Documents;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WebDavService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.webdav.url'), '/');
        $this->username = config('services.webdav.username');
        $this->password = config('services.webdav.password');
    }

    /**
     * Lire / télécharger un fichier depuis WebDAV
     */
    public function get(string $filePath): string
    {
        $url = $this->baseUrl . '/' . ltrim($filePath, '/');

        $response = Http::withBasicAuth(
            $this->username,
            $this->password
        )->get($url);

        if (!$response->successful()) {
            throw new RuntimeException(
                "Erreur WebDAV GET : HTTP {$response->status()} - {$response->body()}"
            );
        }

        return $response->body();
    }

    /**
     * Envoyer / créer un fichier sur WebDAV
     */
    public function put(string $filePath, string $content): bool
    {
        $url = $this->baseUrl . '/' . ltrim($filePath, '/');

        $response = Http::withBasicAuth(
            $this->username,
            $this->password
        )->withBody(
            $content,
            'application/octet-stream'
        )->put($url);

        if (!$response->successful()) {
            throw new RuntimeException(
                "Erreur WebDAV PUT : HTTP {$response->status()} - {$response->body()}"
            );
        }

        return true;
    }
}
