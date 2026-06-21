<?php

namespace IlmLV\GeoIp\Tests\Fixtures;

use IlmLV\GeoIp\Provider\AbstractProvider;

/**
 * A minimal in-memory provider for testing GeoIpLocator without any backend.
 */
final class FakeProvider extends AbstractProvider
{
    /** @var string|null */
    private $isoCode;

    public function __construct(?string $isoCode = 'LV', ?string $attribution = null)
    {
        $this->isoCode = $isoCode;
        $this->attribution = $attribution;
    }

    public function lookup(string $ip): array
    {
        $result = $this->blankResult();
        $result['country']['iso_code'] = $this->isoCode;
        $result['country']['name'] = $this->isoCode ? 'Latvia' : null;
        return $result;
    }
}
