<?php

/**
 * Tiny router for the PHP built-in server, used by ServissItProviderHttpTest to
 * exercise the real HTTP transports without hitting the network.
 *
 *   ?status=NNN  set the response status code (default 200)
 *   ?body=...    set the raw response body (default a minimal GeoIP JSON)
 */

$status = isset($_GET['status']) ? (int) $_GET['status'] : 200;
http_response_code($status);
header('Content-Type: application/json');

echo $_GET['body'] ?? json_encode(['country' => ['iso_code' => 'LV']]);
