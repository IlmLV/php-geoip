<?php

namespace IlmLV\GeoIp\Exception;

use InvalidArgumentException;

/**
 * Thrown when the supplied value is not a valid IPv4 or IPv6 address.
 */
class InvalidIpException extends InvalidArgumentException implements GeoIpException
{
    public static function forIp(string $ip): self
    {
        return new self("`$ip` should be a valid IPv4 or IPv6 address.");
    }
}
