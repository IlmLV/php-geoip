<?php

namespace IlmLV\GeoIp\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Exception\MissingDependencyException;
use IP2Location\Database as IP2LocationDatabase;

/**
 * Reads IP2Location LITE/commercial BIN databases via the optional
 * `ip2location/ip2location-php` package (install it with
 * `composer require ip2location/ip2location-php`).
 *
 * Depending on the BIN edition (DB1..DB11), some fields may be unavailable;
 * IP2Location signals these with sentinel strings that are normalised to null.
 */
class IP2LocationProvider extends AbstractProvider
{
    /** @var string|null */
    private $binPath;

    /** @var int */
    private $mode;

    /** @var IP2LocationDatabase|null */
    private $db;

    /**
     * @param string $binPath Path to an IP2Location .BIN file.
     * @param int    $mode    Lookup mode (FILE_IO / MEMORY_CACHE / SHARED_MEMORY);
     *                        defaults to FILE_IO when the library is present.
     */
    public function __construct(string $binPath, ?int $mode = null)
    {
        $this->guardDependency();
        $this->binPath = $binPath;
        $this->mode = $mode ?? IP2LocationDatabase::FILE_IO;
        $this->attribution = "<a href='https://lite.ip2location.com'>IP Geolocation by IP2Location LITE</a>";
    }

    public static function fromDatabase(IP2LocationDatabase $db): self
    {
        $self = new self('');
        $self->db = $db;
        return $self;
    }

    public function lookup(string $ip): array
    {
        $record = $this->db()->lookup($ip, IP2LocationDatabase::ALL);
        // The library returns false / an empty country code when the IP is absent.
        if ($record === false || $this->clean($record['countryCode'] ?? null) === null) {
            throw AddressNotFoundException::forIp($ip);
        }

        $result = $this->blankResult();
        $result['country']['name'] = $this->clean($record['countryName'] ?? null);
        $result['country']['iso_code'] = $this->clean($record['countryCode'] ?? null);
        $result['region']['name'] = $this->clean($record['regionName'] ?? null);
        $result['city']['name'] = $this->clean($record['cityName'] ?? null);
        $result['location']['latitude'] = $this->numericOrNull($record['latitude'] ?? null);
        $result['location']['longitude'] = $this->numericOrNull($record['longitude'] ?? null);
        $result['zip_code'] = $this->clean($record['zipCode'] ?? null);
        $result['time_zone'] = $this->clean($record['timeZone'] ?? null);

        return $result;
    }

    private function db(): IP2LocationDatabase
    {
        if ($this->db === null) {
            $this->db = new IP2LocationDatabase($this->binPath, $this->mode);
        }
        return $this->db;
    }

    private function guardDependency(): void
    {
        if (!class_exists(IP2LocationDatabase::class)) {
            throw MissingDependencyException::forPackage('ip2location/ip2location-php');
        }
    }

    /**
     * @param mixed $value
     * @return float|null
     */
    private function numericOrNull($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
