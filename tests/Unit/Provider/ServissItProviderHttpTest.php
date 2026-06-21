<?php

namespace IlmLV\GeoIp\Tests\Unit\Provider;

use IlmLV\GeoIp\Exception\RemoteException;
use IlmLV\GeoIp\Provider\ServissItProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the real HTTP transports of ServissItProvider deterministically and
 * without a network server: success paths use the data:// and file:// stream
 * wrappers, failure paths use an unreachable port (instant connection refused).
 */
final class ServissItProviderHttpTest extends TestCase
{
    /** A port nothing listens on; connecting is refused immediately. */
    private const DEAD_URL = 'http://127.0.0.1:1/';

    public function testStreamGetReturnsBodyForAReadableStream(): void
    {
        if (!ini_get('allow_url_fopen')) {
            self::markTestSkipped('allow_url_fopen disabled');
        }
        $json = '{"country":{"iso_code":"LV"}}';
        [$status, $body] = $this->provider()->exposeStreamGet('data://text/plain,' . rawurlencode($json));

        self::assertSame(0, $status); // non-HTTP stream → no status line
        self::assertSame($json, $body);
    }

    public function testStreamGetReturnsNullWhenTheRequestFails(): void
    {
        // null lets httpGet() fall back to cURL.
        self::assertNull($this->provider()->exposeStreamGet(self::DEAD_URL));
    }

    public function testCurlGetReturnsBodyForAReadableUrl(): void
    {
        $this->requireCurl();
        $file = $this->tempJson('{"country":{"iso_code":"US"}}');
        try {
            [$status, $body] = $this->provider()->exposeCurlGet('file://' . $file);
            self::assertSame(0, $status);
            self::assertSame('{"country":{"iso_code":"US"}}', $body);
        } finally {
            @unlink($file);
        }
    }

    public function testCurlGetThrowsOnConnectionFailure(): void
    {
        $this->requireCurl();
        $this->expectException(RemoteException::class);
        $this->provider()->exposeCurlGet(self::DEAD_URL);
    }

    public function testHttpGetDispatchesToTheStreamTransport(): void
    {
        if (!ini_get('allow_url_fopen')) {
            self::markTestSkipped('allow_url_fopen disabled');
        }
        $json = '{"country":{"iso_code":"LV"}}';
        [$status, $body] = $this->provider()->exposeHttpGet('data://text/plain,' . rawurlencode($json));

        self::assertSame(0, $status);
        self::assertSame($json, $body);
    }

    public function testHttpGetThrowsWhenEveryTransportFails(): void
    {
        // Stream returns null, then cURL (if present) errors → RemoteException either way.
        $this->expectException(RemoteException::class);
        $this->provider()->exposeHttpGet(self::DEAD_URL);
    }

    private function requireCurl(): void
    {
        if (!function_exists('curl_init')) {
            self::markTestSkipped('ext-curl not available');
        }
    }

    private function tempJson(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'geoip_test_');
        file_put_contents($file, $contents);
        return $file;
    }

    /**
     * A subclass exposing the protected transport methods for direct testing.
     */
    private function provider(): ServissItProvider
    {
        return new class (1) extends ServissItProvider {
            public function exposeStreamGet(string $url): ?array
            {
                return $this->streamGet($url);
            }

            public function exposeCurlGet(string $url): array
            {
                return $this->curlGet($url);
            }

            public function exposeHttpGet(string $url): array
            {
                return $this->httpGet($url);
            }
        };
    }
}
