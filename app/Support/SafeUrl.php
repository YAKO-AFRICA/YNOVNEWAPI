<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Validation anti-SSRF d'une URL sortante.
 *
 * - schéma http/https (https seul si $httpsOnly), pas d'identifiants dans l'URL
 * - liste blanche d'hôtes optionnelle
 * - TOUTES les IP résolues doivent être publiques (IPv4 + IPv6)
 * - renvoie une option cURL CURLOPT_RESOLVE qui "épingle" l'IP validée :
 *   la requête part vers l'IP contrôlée, pas vers une seconde résolution DNS
 *   (neutralise le DNS rebinding).
 */
final class SafeUrl
{
    /**
     * @return array{url:string, scheme:string, host:string, port:int, ip:string, curl:array}
     *
     * @throws InvalidArgumentException
     */
    public static function resolve(string $url, bool $httpsOnly = false, array $allowedHosts = []): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('URL invalide.');
        }

        $parts  = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host   = strtolower(trim($parts['host'] ?? '', '[]'));

        $allowedSchemes = $httpsOnly ? ['https'] : ['http', 'https'];
        if (!in_array($scheme, $allowedSchemes, true) || $host === '') {
            throw new InvalidArgumentException('Schéma ou hôte non autorisé.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Identifiants dans l\'URL non autorisés.');
        }

        $allowedHosts = array_map('strtolower', array_filter($allowedHosts));
        if ($allowedHosts && !in_array($host, $allowedHosts, true)) {
            throw new InvalidArgumentException('Domaine non autorisé.');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $ips  = self::resolveIps($host);

        if (!$ips) {
            throw new InvalidArgumentException('Hôte introuvable.');
        }

        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                throw new InvalidArgumentException('Cible non autorisée.');
            }
        }

        // IPv4 de préférence
        $pinned = $ips[0];
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $pinned = $ip;
                break;
            }
        }

        $curlAddress = filter_var($pinned, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $pinned . ']' : $pinned;

        return [
            'url'    => $url,
            'scheme' => $scheme,
            'host'   => $host,
            'port'   => $port,
            'ip'     => $pinned,
            'curl'   => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$curlAddress}"]],
        ];
    }

    /** @return string[] */
    private static function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips     = [];
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        foreach ($records as $record) {
            if (!empty($record['ip']))   $ips[] = $record['ip'];
            if (!empty($record['ipv6'])) $ips[] = $record['ipv6'];
        }

        if (!$ips) {
            $ips = @gethostbynamel($host) ?: [];
        }

        return array_values(array_unique($ips));
    }

    public static function isPublicIp(string $ip): bool
    {
        // IPv6 mappée IPv4 (::ffff:127.0.0.1) : on teste l'IPv4 embarquée
        if (stripos($ip, '::ffff:') === 0) {
            $ip = substr($ip, 7);
        }

        $ok = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;

        if (!$ok) {
            return false;
        }

        // CGNAT 100.64.0.0/10 (non couvert par les flags PHP)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long !== false && $long >= ip2long('100.64.0.0') && $long <= ip2long('100.127.255.255')) {
                return false;
            }
        }

        // IPv6 : loopback, link-local, unique-local
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $lower = strtolower($ip);
            if ($lower === '::1' || $lower === '::' || str_starts_with($lower, 'fe80') || str_starts_with($lower, 'fc') || str_starts_with($lower, 'fd')) {
                return false;
            }
        }

        return true;
    }
}