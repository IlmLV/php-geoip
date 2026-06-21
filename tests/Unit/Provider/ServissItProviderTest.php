<?php

namespace IlmLV\GeoIp\Tests\Unit\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;
use IlmLV\GeoIp\Exception\RemoteException;
use IlmLV\GeoIp\Provider\ServissItProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

    public function testAttributionNullWhenServiceOmitsIt(): void
    {
        $provider = $this->providerReturning(['country' => ['iso_code' => 'LV']]);
        $provider->lookup('8.8.8.8');
        self::assertNull($provider->attribution());
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

    public function testFetchMapsHttpResponses(): void
    {
        // 2xx + valid JSON → decoded array reaches lookup().
        $ok = $this->providerForHttp([200, '{"country":{"iso_code":"LV"}}']);
        self::assertSame('LV', $ok->lookup('8.8.8.8')['country']['iso_code']);
    }

    public function testFetchTreats400AsNotFound(): void
    {
        $provider = $this->providerForHttp([400, 'ERROR: not found in database.']);
        $this->expectException(AddressNotFoundException::class);
        $provider->lookup('203.0.113.7');
    }

    public function testFetchTreatsServerErrorAsRemoteException(): void
    {
        $provider = $this->providerForHttp([500, 'oops']);
        $this->expectException(RemoteException::class);
        $provider->lookup('8.8.8.8');
    }

    public function testFetchTreatsInvalidJsonAsRemoteException(): void
    {
        $provider = $this->providerForHttp([200, 'not json']);
        $this->expectException(RemoteException::class);
        $provider->lookup('8.8.8.8');
    }

    public function testStatusFromHeadersReadsLastStatusLine(): void
    {
        $method = new ReflectionMethod(ServissItProvider::class, 'statusFromHeaders');
        if (\PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        $provider = new ServissItProvider();

        self::assertSame(200, $method->invoke($provider, ['HTTP/1.1 301 Moved', 'HTTP/1.1 200 OK']));
        self::assertSame(404, $method->invoke($provider, ['HTTP/2 404 Not Found']));
        self::assertSame(0, $method->invoke($provider, ['X-Not-A-Status: 1']));
    }

    /**
     * Builds a provider whose low-level httpGet() returns a canned [status, body].
     *
     * @param array{0:int,1:string} $response
     */
    private function providerForHttp(array $response): ServissItProvider
    {
        return new class ($response) extends ServissItProvider {
            /** @var array{0:int,1:string} */
            private $response;

            /** @param array{0:int,1:string} $response */
            public function __construct(array $response)
            {
                parent::__construct();
                $this->response = $response;
            }

            protected function httpGet(string $url): array
            {
                return $this->response;
            }
        };
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
