<?php

namespace IlmLV\GeoIp\Support;

/**
 * Small array helpers used when formatting GeoIP results. Previously global
 * functions in the web service; namespaced here to avoid collisions in
 * consumer projects.
 */
final class Arr
{
    /**
     * Flattens a multi-dimensional array into a single level, joining nested
     * keys with $separator (e.g. ['country' => ['name' => 'Latvia']] becomes
     * ['country-name' => 'Latvia']).
     */
    public static function flatten(array $array, string $parentKey = null, string $separator = '-'): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $compositeKey = ($parentKey ? $parentKey . $separator : '') . $key;
            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value, $compositeKey, $separator));
            } else {
                $result[$compositeKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Converts space/snake/kebab/dot case to "Pretty-Case", upper-casing short
     * tokens (e.g. "iso_code" -> "Iso-Code", "ip" -> "IP").
     */
    public static function prettyCase(string $s): string
    {
        $s = strtolower($s);
        $separators = [' ', '_', '-', '·'];
        foreach ($separators as $sep) {
            if (strpos($s, $sep) !== false) {
                $s = implode('-', array_map('ucfirst', explode($sep, $s)));
            }
        }
        if (strlen($s) <= 3) {
            $s = strtoupper($s);
        }
        return ucfirst($s);
    }
}
