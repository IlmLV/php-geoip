<?php

namespace IlmLV\GeoIp\Tests\Unit\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Provider\IpinfoLiteProvider;
use MaxMind\Db\Reader;
use PHPUnit\Framework\TestCase;

final class IpinfoLiteProviderTest extends TestCase
{
    public function testMapsFlatIpinfoRecord(): void
    {
        $provider = IpinfoLiteProvider::fromReader($this->readerReturning([
            'country' => 'Latvia',
            'country_code' => 'LV',
            'continent' => 'Europe',
            'continent_code' => 'EU',
            'asn' => 'AS12345',
            'as_name' => 'Example Telecom',
            'as_domain' => 'example.lv',
        ]));

        $data = $provider->lookup('8.8.8.8');

        self::assertSame('Example Telecom', $data['organisation']);
        self::assertSame('LV', $data['country']['iso_code']);
        self::assertSame('Europe', $data['continent']['name']);
        self::assertSame('EU', $data['continent']['code']);
        // Free tier carries no city/location data.
        self::assertNull($data['city']['name']);
        self::assertNull($data['location']['latitude']);
    }

    public function testMissingRecordThrows(): void
    {
        $provider = IpinfoLiteProvider::fromReader($this->readerReturning(null));
        $this->expectException(AddressNotFoundException::class);
        $provider->lookup('203.0.113.7');
    }

    /**
     * @param array<string, mixed>|null $record
     */
    private function readerReturning($record): Reader
    {
        return new class ($record) extends Reader {
            /** @var array<string, mixed>|null */
            private $record;

            /** @param array<string, mixed>|null $record */
            public function __construct($record)
            {
                $this->record = $record; // intentionally skip parent (no file to open)
            }

            public function get(string $ipAddress)
            {
                return $this->record;
            }
        };
    }
}
