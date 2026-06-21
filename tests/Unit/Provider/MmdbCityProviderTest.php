<?php

namespace IlmLV\GeoIp\Tests\Unit\Provider;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException as GeoIp2AddressNotFound;
use GeoIp2\Model\Asn;
use GeoIp2\Model\City;
use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Provider\MmdbCityProvider;
use PHPUnit\Framework\TestCase;

final class MmdbCityProviderTest extends TestCase
{
    public function testMapsCityAndAsnRecords(): void
    {
        $city = new City([
            'city' => ['names' => ['en' => 'Riga']],
            'country' => ['iso_code' => 'LV', 'names' => ['en' => 'Latvia'], 'is_in_european_union' => true],
            'continent' => ['code' => 'EU', 'names' => ['en' => 'Europe']],
            'subdivisions' => [['iso_code' => 'RIX', 'names' => ['en' => 'Riga']]],
            'location' => ['latitude' => 56.95, 'longitude' => 24.1, 'time_zone' => 'Europe/Riga', 'metro_code' => 5],
            'postal' => ['code' => 'LV-1050'],
        ], ['en']);

        $asn = new Asn([
            'autonomous_system_organization' => 'SIA Example',
            'autonomous_system_number' => 12345,
            'ip_address' => '8.8.8.8',
            'prefix_len' => 24,
        ]);

        $data = $this->provider($city, $asn)->lookup('8.8.8.8');

        self::assertSame('SIA Example', $data['organisation']);
        self::assertSame('Riga', $data['city']['name']);
        self::assertSame('LV', $data['country']['iso_code']);
        self::assertTrue($data['country']['is_in_european_union']);
        self::assertSame('Riga', $data['region']['name']);
        self::assertSame('RIX', $data['region']['iso_code']);
        self::assertSame(56.95, $data['location']['latitude']);
        self::assertSame(24.1, $data['location']['longitude']);
        self::assertSame('LV-1050', $data['zip_code']);
        self::assertSame('Europe/Riga', $data['time_zone']);
    }

    public function testSparseSubdivisionsBecomeNull(): void
    {
        $city = new City([
            'country' => ['iso_code' => 'US', 'names' => ['en' => 'United States']],
        ], ['en']);

        $data = $this->provider($city)->lookup('8.8.8.8');

        self::assertNull($data['region']['name']);
        self::assertNull($data['region']['iso_code']);
        self::assertNull($data['organisation']); // no ASN reader supplied
    }

    public function testAddressNotFoundIsTranslated(): void
    {
        $reader = new class () extends Reader {
            public function __construct()
            {
            }

            public function city(string $ipAddress): City
            {
                throw new GeoIp2AddressNotFound("The address $ipAddress is not in the database.");
            }
        };

        $this->expectException(AddressNotFoundException::class);
        MmdbCityProvider::fromReaders($reader)->lookup('203.0.113.7');
    }

    private function provider(City $city, ?Asn $asn = null): MmdbCityProvider
    {
        $cityReader = new class ($city) extends Reader {
            /** @var City */
            private $city;

            public function __construct(City $city)
            {
                $this->city = $city;
            }

            public function city(string $ipAddress): City
            {
                return $this->city;
            }
        };

        $asnReader = null;
        if ($asn !== null) {
            $asnReader = new class ($asn) extends Reader {
                /** @var Asn */
                private $asn;

                public function __construct(Asn $asn)
                {
                    $this->asn = $asn;
                }

                public function asn(string $ipAddress): Asn
                {
                    return $this->asn;
                }
            };
        }

        return MmdbCityProvider::fromReaders($cityReader, $asnReader);
    }
}
