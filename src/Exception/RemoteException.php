<?php

namespace IlmLV\GeoIp\Exception;

use RuntimeException;

/**
 * Thrown when a remote provider cannot reach or get a usable response from its
 * web service (network failure, unexpected status, unparseable body).
 */
class RemoteException extends RuntimeException implements GeoIpException
{
    public static function forUrl(string $url, string $reason): self
    {
        return new self("Remote lookup via `$url` failed: $reason");
    }
}
