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
        // Map each A-Z letter to its Unicode regional-indicator symbol
        // (U+1F1E6 'A' .. U+1F1FF 'Z') and concatenate the two.
        return implode('', array_map(static function (string $letter): string {
            return mb_chr(ord($letter) + 0x1F1A5, 'UTF-8');
        }, str_split($countryCode)));
    }
}
