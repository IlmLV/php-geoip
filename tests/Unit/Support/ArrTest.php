<?php

namespace IlmLV\GeoIp\Tests\Unit\Support;

use IlmLV\GeoIp\Support\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function testFlattenJoinsNestedKeysWithHyphen(): void
    {
        $flat = Arr::flatten([
            'ip' => '1.2.3.4',
            'country' => ['name' => 'Latvia', 'iso_code' => 'LV'],
        ]);

        self::assertSame('1.2.3.4', $flat['ip']);
        self::assertSame('Latvia', $flat['country-name']);
        self::assertSame('LV', $flat['country-iso_code']);
    }

    public function testPrettyCase(): void
    {
        $cases = [
            'iso_code'     => 'Iso-Code',
            'country-name' => 'Country-Name',
            'ip'           => 'IP',
            'organisation' => 'Organisation',
        ];
        foreach ($cases as $input => $expected) {
            self::assertSame($expected, Arr::prettyCase($input), "prettyCase($input)");
        }
    }
}
