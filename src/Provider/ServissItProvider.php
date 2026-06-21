<?php

namespace IlmLV\GeoIp\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Exception\RemoteException;

/**
 * Looks up IPs via the public ip.serviss.it web service rather than a local
 * database. ip.serviss.it runs this project's web example, so its
 * `GET /?ip=<ip>&format=json` response already matches the normalised schema;
 * this provider is a thin HTTP client over that API.
 *
 * It needs no database, which makes it the zero-configuration default — handy
 * for getting started or low-volume use. For high volume or offline use, prefer
 * a local provider (MmdbCityProvider, IP2LocationProvider, ...).
 *
 * The endpoint is intentionally fixed (no base-URL configuration).
 */
class ServissItProvider extends AbstractProvider
{
    private const BASE_URL = 'https://ip.serviss.it';

    /** @var int */
    private $timeout;

    /**
     * @param int $timeout Network timeout in seconds.
     */
    public function __construct(int $timeout = 5)
    {
        $this->timeout = $timeout;
    }

    public function lookup(string $ip): array
    {
        $json = $this->fetch($ip);

        $result = $this->blankResult();
        $result['organisation'] = $this->clean($json['organisation'] ?? null);
        $result['city']['name'] = $this->clean($json['city']['name'] ?? null);
        $result['country']['name'] = $this->clean($json['country']['name'] ?? null);
        $result['country']['iso_code'] = $this->clean($json['country']['iso_code'] ?? null);
        $result['country']['is_in_european_union'] = $json['country']['is_in_european_union'] ?? null;
        $result['continent']['name'] = $this->clean($json['continent']['name'] ?? null);
        $result['continent']['code'] = $this->clean($json['continent']['code'] ?? null);
        $result['region']['name'] = $this->clean($json['region']['name'] ?? null);
        $result['region']['iso_code'] = $this->clean($json['region']['iso_code'] ?? null);
        $result['location']['latitude'] = $json['location']['latitude'] ?? null;
        $result['location']['longitude'] = $json['location']['longitude'] ?? null;
        $result['zip_code'] = $this->clean($json['zip_code'] ?? null);
        $result['time_zone'] = $this->clean($json['time_zone'] ?? null);
        $result['metro_code'] = $json['metro_code'] ?? null;

        // Surface the source's attribution if the service provides one.
        $this->attribution = isset($json['_attribution']) ? (string) $json['_attribution'] : null;

        return $result;
    }

    /**
     * Fetches and decodes the JSON record for $ip. Protected so tests can stub
     * the network without exposing endpoint configuration.
     *
     * @throws AddressNotFoundException When the service reports the IP as unknown (HTTP 400).
     * @throws RemoteException          On transport failure, unexpected status, or bad body.
     */
    protected function fetch(string $ip): array
    {
        $url = self::BASE_URL . '/?ip=' . urlencode($ip) . '&format=json';
        [$status, $body] = $this->httpGet($url);

        if ($status === 400) {
            throw AddressNotFoundException::forIp($ip);
        }
        if ($status < 200 || $status >= 300) {
            throw RemoteException::forUrl($url, "unexpected HTTP status $status");
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw RemoteException::forUrl($url, 'response was not valid JSON');
        }
        return $decoded;
    }

    /**
     * Performs an HTTP GET, returning [statusCode, body]. Uses the stream
     * wrapper when available and falls back to cURL otherwise.
     *
     * @return array{0:int,1:string}
     * @throws RemoteException On transport failure.
     */
    protected function httpGet(string $url): array
    {
        if (ini_get('allow_url_fopen')) {
            $viaStream = $this->streamGet($url);
            if ($viaStream !== null) {
                return $viaStream;
            }
        }

        if (function_exists('curl_init')) {
            return $this->curlGet($url);
        }

        throw RemoteException::forUrl($url, 'no HTTP transport available (enable allow_url_fopen or ext-curl)');
    }

    /**
     * Fetches via the HTTP stream wrapper. Returns [statusCode, body], or null
     * when the request could not be made so the caller can fall back to cURL.
     *
     * @return array{0:int,1:string}|null
     */
    protected function streamGet(string $url): ?array
    {
        $context = stream_context_create(['http' => [
            'timeout' => $this->timeout,
            'ignore_errors' => true, // read the body even on a 4xx/5xx
            'header' => "Accept: application/json\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return null;
        }
        return [$this->statusFromHeaders($http_response_header ?? []), $body];
    }

    /**
     * Fetches via cURL. Returns [statusCode, body].
     *
     * @return array{0:int,1:string}
     * @throws RemoteException On transport failure.
     */
    protected function curlGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if (\PHP_VERSION_ID < 80000) {
            curl_close($ch); // no-op since 8.0, deprecated in 8.5; still needed on 7.x
        }
        if ($body === false) {
            throw RemoteException::forUrl($url, $error ?: 'cURL request failed');
        }
        return [$status, (string) $body];
    }

    /**
     * Extracts the numeric status code from a $http_response_header array.
     *
     * @param string[] $headers
     */
    private function statusFromHeaders(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
                $status = (int) $m[1]; // keep the last status line (after redirects)
            }
        }
        return $status ?? 0;
    }
}
