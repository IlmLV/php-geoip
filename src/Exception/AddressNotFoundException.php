<?php

namespace IlmLV\GeoIp\Exception;

use RuntimeException;

/**
 * Thrown when a valid IP address is not present in the GeoIP database.
 */
class AddressNotFoundException extends RuntimeException implements GeoIpException
{
    public static function forIp(string $ip): self
    {
        return new self("`$ip` was not found in the database.");
    }
}
