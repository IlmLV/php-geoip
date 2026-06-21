<?php

namespace IlmLV\GeoIp\Tests\Remote;

use IlmLV\GeoIp\GeoIpLocator;
use PHPUnit\Framework\TestCase;

/**
 * Hits the live ip.serviss.it service to verify its response still matches the
 * schema ServissItProvider expects. Network-dependent and intentionally NOT run
 * in pull-request CI — a scheduled workflow runs it to catch upstream drift.
 *
 * Google's public DNS (8.8.8.8) is a stable, reliably-mapped fixture IP.
 */
final class RemoteCompatibilityTest extends TestCase
{
    private const FIXTURE_IP = '8.8.8.8';

    public function testRemoteServiceMatchesExpectedSchema(): void
    {
        $result = (new GeoIpLocator())->locate(self::FIXTURE_IP);

        // The full normalised shape must be present...
        $keys = [
            'ip', 'organisation', 'city', 'country', 'continent',
            'region', 'location', 'zip_code', 'time_zone', 'metro_code',
        ];
        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $result, "missing top-level key `$key` — remote schema may have changed");
        }
        self::assertArrayHasKey('iso_code', $result['country']);
        self::assertArrayHasKey('latitude', $result['location']);

        // ...and resolve to the expected country, proving data still flows through.
        self::assertSame(self::FIXTURE_IP, $result['ip']);
        $msg = 'remote lookup no longer resolves the fixture IP to the United States';
        self::assertSame('US', $result['country']['iso_code'], $msg);
        self::assertSame('🇺🇸', $result['country']['flag']['emoji']);
    }
}
