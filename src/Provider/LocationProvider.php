<?php

namespace IlmLV\GeoIp\Provider;

use IlmLV\GeoIp\Exception\AddressNotFoundException;

/**
 * A source of IP geolocation data. Implementations wrap a specific database
 * backend (a MaxMind-format MMDB reader, an IP2Location BIN reader, etc.) and
 * normalise its records into a common array shape.
 *
 * The returned array deliberately omits `ip` and `country.flag`; those are
 * cross-cutting concerns added by {@see \IlmLV\GeoIp\GeoIpLocator} so they apply
 * uniformly across every provider.
 */
interface LocationProvider
{
    /**
     * Resolves an IP address to the normalised geo array. Every implementation
     * returns the same keys (see {@see AbstractProvider::blankResult()}); fields
     * a given source cannot supply are `null`.
     *
     * @throws AddressNotFoundException When the IP is absent from the database.
     */
    public function lookup(string $ip): array;

    /**
     * The attribution this data source legally requires displayed (HTML or
     * plain text), or null when none is required.
     */
    public function attribution(): ?string;
}
