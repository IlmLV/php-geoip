<?php

namespace IlmLV\GeoIp\Tests\Unit\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Provider\IP2LocationProvider;
use IP2Location\Database;
use PHPUnit\Framework\TestCase;

final class IP2LocationProviderTest extends TestCase
{
    public function testMapsBinRecordAndNormalisesSentinels(): void
    {
        $db = new Database();
        $db->setRecord([
            'countryCode' => 'LV',
            'countryName' => 'Latvia',
            'regionName' => 'Riga',
            'cityName' => 'Riga',
            'latitude' => 56.95,
            'longitude' => 24.1,
            'zipCode' => '-',            // IP2Location "no data" sentinel
            'timeZone' => '+02:00',
        ]);

        $data = IP2LocationProvider::fromDatabase($db)->lookup('8.8.8.8');

        self::assertSame('Latvia', $data['country']['name']);
        self::assertSame('LV', $data['country']['iso_code']);
        self::assertSame('Riga', $data['city']['name']);
        self::assertSame(56.95, $data['location']['latitude']);
        self::assertSame('+02:00', $data['time_zone']);
        self::assertNull($data['zip_code']); // "-" normalised to null
    }

    public function testEmptyCountryThrowsNotFound(): void
    {
        $db = new Database();
        $db->setRecord(['countryCode' => '-']);

        $this->expectException(AddressNotFoundException::class);
        IP2LocationProvider::fromDatabase($db)->lookup('0.0.0.0');
    }

    public function testFalseRecordThrowsNotFound(): void
    {
        $db = new Database();
        $db->setRecord(false); // the library returns false when the IP is absent

        $this->expectException(AddressNotFoundException::class);
        IP2LocationProvider::fromDatabase($db)->lookup('0.0.0.0');
    }

    public function testNonNumericCoordinatesBecomeNull(): void
    {
        $db = new Database();
        $db->setRecord([
            'countryCode' => 'US',
            'countryName' => 'United States',
            'latitude' => '-',     // lower BIN editions omit coordinates
            'longitude' => 'N/A',
        ]);

        $data = IP2LocationProvider::fromDatabase($db)->lookup('8.8.8.8');

        self::assertNull($data['location']['latitude']);
        self::assertNull($data['location']['longitude']);
    }

    public function testAttributionIsSet(): void
    {
        $db = new Database();
        self::assertStringContainsString('ip2location.com', IP2LocationProvider::fromDatabase($db)->attribution());
    }
}
