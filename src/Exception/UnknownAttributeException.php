<?php

namespace IlmLV\GeoIp\Exception;

use InvalidArgumentException;

/**
 * Thrown when a requested single attribute does not exist in the result set.
 */
class UnknownAttributeException extends InvalidArgumentException implements GeoIpException
{
    public static function forAttribute(string $attribute): self
    {
        return new self("`$attribute` is not a known attribute.");
    }
}
