<?php

namespace App\Services\Storage;

use League\Flysystem\Config;
use League\Flysystem\WebDAV\WebDAVAdapter;

/**
 * Adapter WebDAV qui bufferise les uploads streamés en mémoire.
 *
 * Sabre/Guzzle échoue sur les PUT avec body stream non-rewindable
 * ("necessary data rewind wasn't possible") avec certains serveurs
 * Nextcloud derrière proxy. On lit le stream puis on délègue à write().
 */
class BufferedWebDavAdapter extends WebDAVAdapter
{
    public function writeStream(string $path, $contents, Config $config): void
    {
        $data = stream_get_contents($contents);

        if ($data === false) {
            $data = '';
        }

        $this->write($path, $data, $config);
    }
}
