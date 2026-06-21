<?php

namespace IlmLV\GeoIp\Support;

use InvalidArgumentException;

/**
 * Country flag helpers derived from an ISO 3166-1 alpha-2 country code.
 */
final class Flags
{
    /**
     * Builds the Unicode regional-indicator flag emoji for a 2-letter country
     * code (e.g. "LV" -> 🇱🇻).
     */
    public static function emoji(string $countryCode): string
    {
        if (strlen($countryCode) !== 2) {
            throw new InvalidArgumentException('Please provide a 2 character country code.');
        }
        $countryCode = strtoupper($countryCode);
        return implode('', array_map(static function ($s) {
            $i = ord($s) + 127397;
            return mb_convert_encoding('&#' . $i . ';', 'UTF-8', 'HTML-ENTITIES');
        }, str_split($countryCode)));
    }
}
