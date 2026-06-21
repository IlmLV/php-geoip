<?php

namespace IlmLV\GeoIp;

use GeoIp2\Database\Reader;
use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Exception\InvalidIpException;
use IlmLV\GeoIp\Provider\LocationProvider;
use IlmLV\GeoIp\Provider\MmdbCityProvider;
use IlmLV\GeoIp\Provider\ServissItProvider;
use IlmLV\GeoIp\Support\Flags;

/**
 * Resolves an IP address to a structured location array.
 *
 * The actual lookup is delegated to a {@see LocationProvider}, so the same API
 * works across backends — the remote ip.serviss.it service, MaxMind GeoLite2,
 * DB-IP Lite, IPLocate, IPinfo Lite (MMDB) and IP2Location (BIN). This class
 * adds the cross-cutting `ip` field and country flag on top of whatever the
 * provider returns.
 *
 * With no database path, lookups go through the remote ip.serviss.it service —
 * a zero-configuration default. No database is bundled (license terms vary by
 * source); for local lookups supply your own. See bin/geoip-update to download one.
 */
class GeoIpLocator
{
    /** @var LocationProvider */
    private $provider;

    /** @var string|null */
    private $flagBaseUrl;

    /**
     * With a City database path, reads a MaxMind GeoLite2 (or compatible) .mmdb,
     * optionally augmented by an ASN database. With no path (null), lookups go
     * through the remote ip.serviss.it service — the zero-configuration default.
     *
     * @param string|null $cityDbPath  Path to a City-schema .mmdb file; null uses the remote service.
     * @param string|null $asnDbPath   Path to an ASN .mmdb file (optional; enables `organisation`).
     * @param string|null $flagBaseUrl Optional base URL for flag SVGs (e.g. "//example.com/images/flags");
     *                                 when set, results include country.flag.url.
     */
    public function __construct(?string $cityDbPath = null, ?string $asnDbPath = null, ?string $flagBaseUrl = null)
    {
        $this->provider = $cityDbPath === null
            ? new ServissItProvider()
            : MmdbCityProvider::maxmind($cityDbPath, $asnDbPath);
        $this->flagBaseUrl = $flagBaseUrl !== null ? rtrim($flagBaseUrl, '/') : null;
    }

    /**
     * Builds a locator around any {@see LocationProvider}.
     */
    public static function withProvider(LocationProvider $provider, ?string $flagBaseUrl = null): self
    {
        $self = new self(null, null, $flagBaseUrl);
        $self->provider = $provider;
        return $self;
    }

    /**
     * Backward-compatible factory accepting pre-built MaxMind readers.
     */
    public static function fromReaders(Reader $city, ?Reader $asn = null, ?string $flagBaseUrl = null): self
    {
        return self::withProvider(MmdbCityProvider::fromReaders($city, $asn), $flagBaseUrl);
    }

    /**
     * Resolves an IP address to a structured location array.
     *
     * @throws InvalidIpException       When $ip is not a valid IPv4/IPv6 address.
     * @throws AddressNotFoundException When $ip is not present in the database.
     */
    public function locate(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw InvalidIpException::forIp($ip);
        }

        $data = $this->provider->lookup($ip);

        $isoCode = $data['country']['iso_code'];
        $flag = ['emoji' => $isoCode ? Flags::emoji($isoCode) : null];
        if ($this->flagBaseUrl !== null && $isoCode) {
            $flag['url'] = $this->flagBaseUrl . '/' . strtolower($isoCode) . '.svg';
        }
        $data['country']['flag'] = $flag;

        return ['ip' => $ip] + $data;
    }

    /**
     * The attribution the active data source requires displayed, or null.
     */
    public function attribution(): ?string
    {
        return $this->provider->attribution();
    }
}
