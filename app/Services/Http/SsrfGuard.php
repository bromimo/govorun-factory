<?php

namespace App\Services\Http;

/** Защита от SSRF: запрет приватных и зарезервированных IP при HTTP-запросе. */
class SsrfGuard
{
    /** Проверить, разрешён ли URL для исходящего запроса. */
    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'];
        $host = trim($host, '[]');

        $ips = $this->resolve($host);
        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false) {
            return [];
        }

        $ips = [];

        foreach ($records as $r) {
            if (isset($r['ip'])) {
                $ips[] = $r['ip'];
            } elseif (isset($r['ipv6'])) {
                $ips[] = $r['ipv6'];
            }
        }

        return $ips;
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
