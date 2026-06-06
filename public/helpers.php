<?php

/**
 * Determines the real client IP, accounting for reverse proxies / CDNs.
 *
 * Checks forwarding headers in order of trust before falling back to the
 * direct connection address, so the visitor IP is reported correctly when the
 * site sits behind Cloudflare (CF-Connecting-IP / True-Client-IP) or another
 * proxy (X-Forwarded-For). Each candidate is validated, so a malformed or
 * spoofed-empty header is skipped rather than breaking the lookup.
 *
 * @return string|null The first valid IP found, or null if none are valid
 */
function getClientIp(): ?string {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_TRUE_CLIENT_IP']   ?? null,
    ];
    // X-Forwarded-For is a comma-separated chain; the first entry is the client.
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
            $candidates[] = trim($part);
        }
    }
    $candidates[] = $_SERVER['REMOTE_ADDR'] ?? null;

    foreach ($candidates as $ip) {
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return null;
}

/**
 * Returns the host the request was made to, so responses reference the domain
 * currently in use rather than a hardcoded one. Cloudflare preserves the
 * original Host header, so this is correct when behind the CDN. Note the Host
 * header is client-supplied; fall back to SERVER_NAME, then a known default.
 *
 * @return string e.g. "ip.serviss.it" or "127.0.0.1:8080"
 */
function getCurrentHost(): string {
    return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'ip.serviss.it';
}

/**
 * Converts multi dimensional arrays to single level array
 * @param array $array
 * @param string|null $parentKey
 * @param string $separator
 * @return array|false
 */
function flattenArrayKeys(array $array, string $parentKey = null, string $separator = '-'):array {
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flattenArrayKeys($value, ($parentKey ? $parentKey . $separator:'') . $key));
        }
        else {
            $result[($parentKey ? $parentKey . $separator:'') . $key] = $value;
        }
    }
    return $result;
}

/**
 * Case-insensitive search for present array key value
 * @param string $needle
 * @param array $haystack
 * @return mixed|bool The present key value, or false
 */
function insensitiveKeyValue(string $needle, array $haystack) {
    foreach ($haystack as $key => $value) {
        if (strtolower($needle) == strtolower($key)) {
            return $value;
        }
    }
    return false;
}

/**
 * @param string $s space case|snake_case|kebab-case|dot.case|Mix-e.d_Case
 * @return string In-Pretty-Case
 */
function prettyCase(string $s): string {
    $s = strtolower($s);
    $separators = [' ', '_', '-', '·'];
    foreach ($separators as $sep) {
        if(strpos($s, $sep) !== false) {
            $s = implode('-', array_map('ucfirst', explode($sep, $s)));
        }
    }
    if (strlen($s) <= 3) {
        $s = strtoupper($s);
    }
    return ucfirst($s);
}

/**
 * @param string $countryCode
 * @return string
 */
function getCountryFlagEmoji(string $countryCode)
{
    if (strlen($countryCode) !== 2) {
        throw new \InvalidArgumentException('Please provide a 2 character country code.');
    }
    $countryCode = strtoupper($countryCode);
    return implode('', array_map(function($s){
        $i = ord($s) + 127397;
        return mb_convert_encoding('&#'. $i .';', 'UTF-8', 'HTML-ENTITIES');
    }, str_split($countryCode)));
}
