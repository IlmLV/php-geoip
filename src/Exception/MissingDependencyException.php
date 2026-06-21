<?php

namespace IlmLV\GeoIp\Exception;

use RuntimeException;

/**
 * Thrown when a provider requires an optional Composer package that is not
 * installed.
 */
class MissingDependencyException extends RuntimeException implements GeoIpException
{
    public static function forPackage(string $package): self
    {
        return new self("This provider requires the `$package` package. Install it with `composer require $package`.");
    }
}
