<?php

namespace IlmLV\GeoIp\Tests\Unit;

use IlmLV\GeoIp\Exception\UnknownAttributeException;
use IlmLV\GeoIp\ResponseFormatter;
use PHPUnit\Framework\TestCase;

final class ResponseFormatterTest extends TestCase
{
    /** @var array<string, mixed> */
    private $attr = [
        'ip' => '8.8.8.8',
        'organisation' => null,
        'country' => ['name' => 'Latvia', 'iso_code' => 'LV', 'is_in_european_union' => true],
        'time_zone' => 'Europe/Riga',
    ];

    public function testToJsonIsValidAndRoundTrips(): void
    {
        $json = (new ResponseFormatter())->toJson($this->attr);
        self::assertSame($this->attr, json_decode($json, true));
    }

    public function testToPlainTextRendersPrettyKeysAndNaForNull(): void
    {
        $text = (new ResponseFormatter())->toPlainText($this->attr);

        self::assertStringContainsString('IP: 8.8.8.8', $text);
        self::assertStringContainsString('Country-Name: Latvia', $text);
        self::assertStringContainsString('Organisation: N/A', $text); // null → placeholder
    }

    public function testPluckIsSeparatorAndCaseInsensitive(): void
    {
        $formatter = new ResponseFormatter();
        $cases = [
            'ip'                => '8.8.8.8',
            'country-iso-code'  => 'LV',
            'country_iso_code'  => 'LV',
            'Country-Iso-Code'  => 'LV',
        ];
        foreach ($cases as $what => $expected) {
            self::assertSame($expected, $formatter->pluck($this->attr, $what), "pluck($what)");
        }
    }

    public function testPluckUnknownAttributeThrows(): void
    {
        $this->expectException(UnknownAttributeException::class);
        (new ResponseFormatter())->pluck($this->attr, 'does-not-exist');
    }

    public function testPluckReturnsNestedSubArray(): void
    {
        $country = (new ResponseFormatter())->pluck($this->attr, 'country');
        self::assertIsArray($country);
        self::assertSame('LV', $country['iso_code']);
    }

    public function testCustomMissingValuePlaceholder(): void
    {
        $text = (new ResponseFormatter('—'))->toPlainText(['organisation' => null]);
        self::assertSame('Organisation: —' . PHP_EOL, $text);
    }

    public function testToPlainTextFlattensNestedKeysAndBlankToPlaceholder(): void
    {
        $text = (new ResponseFormatter())->toPlainText([
            'country' => ['name' => 'Latvia', 'iso_code' => ''],
        ]);
        self::assertStringContainsString('Country-Name: Latvia', $text);
        self::assertStringContainsString('Country-Iso-Code: N/A', $text); // '' → placeholder
    }
}
