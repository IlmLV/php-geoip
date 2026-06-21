<?php

namespace IlmLV\GeoIp\Provider;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException as GeoIp2AddressNotFound;
use IlmLV\GeoIp\Exception\AddressNotFoundException;
use Throwable;

/**
 * Reads City-schema MaxMind DB (.mmdb) files via the official
 * {@see \GeoIp2\Database\Reader}. This covers MaxMind GeoLite2-City as well as
 * any schema-compatible database — notably DB-IP Lite and IPLocate.io, which
 * deliberately mirror the GeoLite2-City record layout.
 *
 * An optional second database supplies ASN/organisation data
 * (GeoLite2-ASN or the equivalent), since City databases do not carry it.
 */
class MmdbCityProvider extends AbstractProvider
{
    /** @var string|null */
    private $cityDbPath;

    /** @var string|null */
    private $asnDbPath;

    /** @var Reader|null */
    private $cityReader;

    /** @var Reader|null */
    private $asnReader;

    /**
     * @param string      $cityDbPath  Path to a City-schema .mmdb (required).
     * @param string|null $asnDbPath   Path to an ASN .mmdb (optional; enables `organisation`).
     * @param string|null $attribution Source attribution to surface, if any.
     */
    public function __construct(string $cityDbPath, ?string $asnDbPath = null, ?string $attribution = null)
    {
        $this->cityDbPath = $cityDbPath;
        $this->asnDbPath = $asnDbPath;
        $this->attribution = $attribution;
    }

    /**
     * MaxMind GeoLite2. Its EULA does not mandate inline attribution, so none is set.
     */
    public static function maxmind(string $cityDbPath, ?string $asnDbPath = null): self
    {
        return new self($cityDbPath, $asnDbPath, null);
    }

    /**
     * DB-IP Lite (CC BY 4.0) — requires a visible link back to db-ip.com.
     */
    public static function dbip(string $cityDbPath, ?string $asnDbPath = null): self
    {
        return new self($cityDbPath, $asnDbPath, "<a href='https://db-ip.com'>IP Geolocation by DB-IP</a>");
    }

    /**
     * IPLocate.io (CC BY-SA 4.0) — requires attribution to IPLocate.
     */
    public static function iplocate(string $cityDbPath, ?string $asnDbPath = null): self
    {
        return new self($cityDbPath, $asnDbPath, "<a href='https://www.iplocate.io'>IP Geolocation by IPLocate.io</a>");
    }

    /**
     * Builds a provider from pre-constructed readers (dependency injection / testing).
     */
    public static function fromReaders(Reader $city, ?Reader $asn = null, ?string $attribution = null): self
    {
        $self = new self('', null, $attribution);
        $self->cityReader = $city;
        $self->asnReader = $asn;
        return $self;
    }

    public function lookup(string $ip): array
    {
        try {
            $city = $this->cityReader()->city($ip);
        } catch (GeoIp2AddressNotFound $e) {
            throw AddressNotFoundException::forIp($ip);
        }

        $result = $this->blankResult();

        if ($this->asnReader() !== null) {
            try {
                $result['organisation'] = $this->asnReader()->asn($ip)->autonomousSystemOrganization;
            } catch (Throwable $e) {
                // ASN data is optional; absence must not fail the lookup.
            }
        }

        $result['city']['name'] = $city->city->name;
        $result['country']['name'] = $city->country->name;
        $result['country']['iso_code'] = $city->country->isoCode;
        $result['country']['is_in_european_union'] = $city->country->isInEuropeanUnion;
        $result['continent']['name'] = $city->continent->name;
        $result['continent']['code'] = $city->continent->code;
        $result['region']['name'] = $city->subdivisions ? $city->subdivisions[0]->name : null;
        $result['region']['iso_code'] = $city->subdivisions ? $city->subdivisions[0]->isoCode : null;
        $result['location']['latitude'] = $city->location->latitude;
        $result['location']['longitude'] = $city->location->longitude;
        $result['zip_code'] = $city->postal->code;
        $result['time_zone'] = $city->location->timeZone;
        $result['metro_code'] = $city->location->metroCode;

        return $result;
    }

    private function cityReader(): Reader
    {
        if ($this->cityReader === null) {
            $this->cityReader = new Reader($this->cityDbPath);
        }
        return $this->cityReader;
    }

    private function asnReader(): ?Reader
    {
        if ($this->asnReader === null && $this->asnDbPath !== null) {
            $this->asnReader = new Reader($this->asnDbPath);
        }
        return $this->asnReader;
    }
}
