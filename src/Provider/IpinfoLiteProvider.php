<?php

namespace IlmLV\GeoIp\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use MaxMind\Db\Reader;

/**
 * Reads the IPinfo Lite MMDB. Unlike GeoLite2-City, IPinfo Lite uses a flat
 * record schema and only provides country, continent and ASN data on the free
 * tier, so it is read with the low-level {@see \MaxMind\Db\Reader} rather than
 * the City model.
 *
 * Expected record keys: country, country_code, continent, continent_code,
 * asn, as_name, as_domain.
 */
class IpinfoLiteProvider extends AbstractProvider
{
    /** @var string|null */
    private $dbPath;

    /** @var Reader|null */
    private $reader;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
        $this->attribution = "<a href='https://ipinfo.io'>IP address data powered by IPinfo</a>";
    }

    public static function fromReader(Reader $reader): self
    {
        $self = new self('');
        $self->reader = $reader;
        return $self;
    }

    public function lookup(string $ip): array
    {
        $record = $this->reader()->get($ip);
        if (!is_array($record)) {
            throw AddressNotFoundException::forIp($ip);
        }

        $result = $this->blankResult();
        $result['organisation'] = $this->clean($record['as_name'] ?? null);
        $result['country']['name'] = $this->clean($record['country'] ?? null);
        $result['country']['iso_code'] = $this->clean($record['country_code'] ?? null);
        $result['continent']['name'] = $this->clean($record['continent'] ?? null);
        $result['continent']['code'] = $this->clean($record['continent_code'] ?? null);

        return $result;
    }

    private function reader(): Reader
    {
        if ($this->reader === null) {
            $this->reader = new Reader($this->dbPath);
        }
        return $this->reader;
    }
}
