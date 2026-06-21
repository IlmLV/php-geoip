<?php

namespace IlmLV\GeoIp\Tests\Unit;

use IlmLV\GeoIp\Exception\InvalidIpException;
use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\Provider\ServissItProvider;
use IlmLV\GeoIp\Tests\Fixtures\FakeProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class GeoIpLocatorTest extends TestCase
{
    public function testLocatePrependsIpAndAddsFlagEmoji(): void
    {
        $result = GeoIpLocator::withProvider(new FakeProvider('LV'))->locate('8.8.8.8');

        // `ip` is added by the locator and comes first.
        self::assertSame('8.8.8.8', $result['ip']);
        self::assertSame('ip', array_keys($result)[0]);
        self::assertSame('🇱🇻', $result['country']['flag']['emoji']);
        self::assertArrayNotHasKey('url', $result['country']['flag']); // no base URL given
    }

    public function testFlagUrlBuiltFromBaseUrl(): void
    {
        $result = GeoIpLocator::withProvider(new FakeProvider('LV'), '//cdn.example.com/flags/')
            ->locate('8.8.8.8');

        self::assertSame('//cdn.example.com/flags/lv.svg', $result['country']['flag']['url']);
    }

    public function testNoFlagEmojiWhenCountryUnknown(): void
    {
        $result = GeoIpLocator::withProvider(new FakeProvider(null))->locate('8.8.8.8');
        self::assertNull($result['country']['flag']['emoji']);
    }

    public function testInvalidIpThrowsBeforeProviderIsCalled(): void
    {
        $this->expectException(InvalidIpException::class);
        GeoIpLocator::withProvider(new FakeProvider())->locate('not-an-ip');
    }

    public function testAttributionIsDelegatedToProvider(): void
    {
        $locator = GeoIpLocator::withProvider(new FakeProvider('LV', 'Credit me'));
        self::assertSame('Credit me', $locator->attribution());
    }

    public function testDefaultConstructorUsesRemoteProvider(): void
    {
        // The default provider is resolved lazily; invoke the private resolver.
        $method = new ReflectionMethod(GeoIpLocator::class, 'provider');
        if (\PHP_VERSION_ID < 80100) {
            $method->setAccessible(true); // required before PHP 8.1, a no-op (and deprecated) after
        }
        self::assertInstanceOf(ServissItProvider::class, $method->invoke(new GeoIpLocator()));
    }
}
