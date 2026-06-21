<?php

namespace IlmLV\GeoIp\Tests\Unit\Exception;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Exception\GeoIpException;
use IlmLV\GeoIp\Exception\InvalidIpException;
use IlmLV\GeoIp\Exception\MissingDependencyException;
use IlmLV\GeoIp\Exception\RemoteException;
use IlmLV\GeoIp\Exception\UnknownAttributeException;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testInvalidIpExceptionFactory(): void
    {
        $e = InvalidIpException::forIp('nope');
        self::assertInstanceOf(GeoIpException::class, $e);
        self::assertStringContainsString('nope', $e->getMessage());
    }

    public function testAddressNotFoundExceptionFactory(): void
    {
        $e = AddressNotFoundException::forIp('203.0.113.7');
        self::assertInstanceOf(GeoIpException::class, $e);
        self::assertStringContainsString('203.0.113.7', $e->getMessage());
    }

    public function testUnknownAttributeExceptionFactory(): void
    {
        $e = UnknownAttributeException::forAttribute('bogus');
        self::assertInstanceOf(GeoIpException::class, $e);
        self::assertStringContainsString('bogus', $e->getMessage());
    }

    public function testMissingDependencyExceptionFactory(): void
    {
        $e = MissingDependencyException::forPackage('vendor/pkg');
        self::assertInstanceOf(GeoIpException::class, $e);
        self::assertStringContainsString('vendor/pkg', $e->getMessage());
    }

    public function testRemoteExceptionFactory(): void
    {
        $e = RemoteException::forUrl('https://example.test', 'boom');
        self::assertInstanceOf(GeoIpException::class, $e);
        self::assertStringContainsString('https://example.test', $e->getMessage());
        self::assertStringContainsString('boom', $e->getMessage());
    }

    public function testEveryExceptionImplementsTheMarkerInterface(): void
    {
        // The whole package can be caught via the single GeoIpException interface.
        $exceptions = [
            InvalidIpException::forIp('x'),
            AddressNotFoundException::forIp('x'),
            UnknownAttributeException::forAttribute('x'),
            MissingDependencyException::forPackage('x'),
            RemoteException::forUrl('x', 'y'),
        ];
        foreach ($exceptions as $e) {
            self::assertInstanceOf(GeoIpException::class, $e);
            self::assertInstanceOf(\Throwable::class, $e);
        }
    }
}
