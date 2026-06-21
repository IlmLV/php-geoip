<?php

namespace IlmLV\GeoIp\Provider;

/**
 * Shared behaviour for {@see LocationProvider} implementations: the canonical
 * result skeleton and small normalisation helpers.
 */
abstract class AbstractProvider implements LocationProvider
{
    /** @var string|null */
    protected $attribution;

    public function attribution(): ?string
    {
        return $this->attribution;
    }

    /**
     * The normalised result shape with every field null. Providers fill in what
     * they can, guaranteeing all providers return an identical structure.
     */
    protected function blankResult(): array
    {
        return [
            'organisation' => null,
            'city' => ['name' => null],
            'country' => [
                'name' => null,
                'iso_code' => null,
                'is_in_european_union' => null,
            ],
            'continent' => ['name' => null, 'code' => null],
            'region' => ['name' => null, 'iso_code' => null],
            'location' => ['latitude' => null, 'longitude' => null],
            'zip_code' => null,
            'time_zone' => null,
            'metro_code' => null,
        ];
    }

    /**
     * Coerces empty strings and known "no data" sentinels to null. Different
     * backends signal absence differently (IP2Location uses "-" and
     * "Not_supported"/"This parameter is unavailable..."); this keeps the
     * normalised output clean.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function clean($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '-' || stripos($trimmed, 'not_supported') !== false
            || stripos($trimmed, 'unavailable') !== false) {
            return null;
        }
        return $trimmed;
    }
}
