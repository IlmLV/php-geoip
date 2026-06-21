<?php

namespace IlmLV\GeoIp;

use IlmLV\GeoIp\Exception\UnknownAttributeException;
use IlmLV\GeoIp\Support\Arr;

/**
 * Formats a structured location array (as returned by GeoIpLocator::locate())
 * into JSON or plain text, and supports plucking a single attribute.
 */
class ResponseFormatter
{
    /** @var string */
    private $missingValue;

    /**
     * @param string $missingValue Placeholder shown for null values in plain text.
     */
    public function __construct(string $missingValue = 'N/A')
    {
        $this->missingValue = $missingValue;
    }

    /**
     * Encodes the result as a JSON string.
     */
    public function toJson(array $attr): string
    {
        return json_encode($attr);
    }

    /**
     * Renders the result as flat "Pretty-Key: value" lines.
     */
    public function toPlainText(array $attr): string
    {
        $lines = [];
        foreach (Arr::flatten($attr) as $key => $value) {
            $display = ($value === null || $value === '') ? $this->missingValue : $value;
            $lines[] = Arr::prettyCase($key) . ': ' . $display;
        }
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * Returns a single attribute by name. Accepts nested keys (e.g. "country")
     * and flattened keys, matching the form shown in plain-text output:
     * matching is case-insensitive and treats "-" and "_" as equivalent, so
     * both "country-iso-code" and "country_iso_code" resolve.
     *
     * @return mixed
     * @throws UnknownAttributeException When the attribute is unknown.
     */
    public function pluck(array $attr, string $what)
    {
        $candidates = $attr + Arr::flatten($attr);
        $needle = $this->normalizeKey($what);
        foreach ($candidates as $key => $value) {
            if ($this->normalizeKey((string) $key) === $needle) {
                return $value;
            }
        }
        throw UnknownAttributeException::forAttribute($what);
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(str_replace('_', '-', $key));
    }
}
