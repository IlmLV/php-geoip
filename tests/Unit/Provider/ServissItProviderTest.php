<?php

namespace IlmLV\GeoIp\Tests\Unit\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Provider\ServissItProvider;
use PHPUnit\Framework\TestCase;

final class ServissItProviderTest extends TestCase
{
    public function testMapsRemoteJsonAndSurfacesAttribution(): void
    {
        $provider = $this->providerReturning([
            'ip' => '8.8.8.8',
            'organisation' => 'SIA Example',
            'city' => ['name' => 'Riga'],
            'country' => [
                'name' => 'Latvia',
                'iso_code' => 'LV',
                'is_in_european_union' => true,
                'flag' => ['emoji' => 'IGNORED', 'url' => '//ip.serviss.it/x.svg'],
            ],
            'continent' => ['name' => 'Europe', 'code' => 'EU'],
            'region' => ['name' => 'Riga', 'iso_code' => 'RIX'],
            'location' => ['latitude' => 56.95, 'longitude' => 24.1],
            'zip_code' => 'LV-1050',
            'time_zone' => 'Europe/Riga',
            'metro_code' => null,
            '_attribution' => 'Credit DB-IP',
        ]);

        $data = $provider->lookup('8.8.8.8');

        self::assertSame('SIA Example', $data['organisation']);
        self::assertSame('Riga', $data['city']['name']);
        self::assertSame('LV', $data['country']['iso_code']);
        self::assertTrue($data['country']['is_in_european_union']);
        self::assertSame(24.1, $data['location']['longitude']);
        // The provider returns geo data only — `ip` and `country.flag` are the locator's job.
        self::assertArrayNotHasKey('ip', $data);
        self::assertArrayNotHasKey('flag', $data['country']);
        self::assertSame('Credit DB-IP', $provider->attribution());
    }

    public function testFetchFailurePropagates(): void
    {
        $provider = new class () extends ServissItProvider {
            protected function fetch(string $ip): array
            {
                throw AddressNotFoundException::forIp($ip);
            }
        };

        $this->expectException(AddressNotFoundException::class);
        $provider->lookup('203.0.113.7');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function providerReturning(array $payload): ServissItProvider
    {
        return new class ($payload) extends ServissItProvider {
            /** @var array<string, mixed> */
            private $payload;

            /** @param array<string, mixed> $payload */
            public function __construct(array $payload)
            {
                parent::__construct();
                $this->payload = $payload;
            }

            protected function fetch(string $ip): array
            {
                return $this->payload;
            }
        };
    }
}
