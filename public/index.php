<?php

if(php_sapi_name() == 'cli')
    die("Currently no support for CLI.\n");

require '../vendor/autoload.php';
require 'helpers.php';

$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_GET['ip'])) {
    $ip = $_GET['ip'];
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo "ERROR: `ip` attribute `$ip` should be valid IPv4 or IPv6.";
        exit(1);
    }
}

// Load GeoIP
use GeoIp2\Database\Reader;
$cityReader = new Reader('../geoip/GeoLite2-City.mmdb');
$ispReader = new Reader('../geoip/GeoLite2-ASN.mmdb');

try {
    $city = $cityReader->city($ip);
} catch (\Exception $e) {
    http_response_code(400);
    echo "ERROR: `ip` attribute `$ip` not found in database.";
    exit(1);
}

$asn = null;
try {
    $asn = $ispReader->asn($ip);
} catch (\Exception $e) {}

$attr = [
    'ip' => $ip,
    'organisation' => $asn ? $asn->autonomousSystemOrganization : null,
    'city' => [
        'name' => $city->city->name
    ],
    'country' => [
        'name' => $city->country->name,
        'iso_code' => $city->country->isoCode,
        'is_in_european_union' => $city->country->isInEuropeanUnion,
        'flag' => [
            'emoji' => getCountryFlagEmoji($city->country->isoCode),
            'url' => '//ip.serviss.it/images/flags/'. strtolower($city->country->isoCode) .'.svg',
        ]
        //'calling_code' => '371',
        //'capital' => 'Riga',
        //'currency' => [
        //    'code' => 'EUR',
        //    'symbol' => '€'
        //]
    ],
    'continent' => [
        'name' => $city->continent->name,
        'code' => $city->continent->code
    ],
    'region' => [
        'name' => $city->subdivisions ? $city->subdivisions[0]->name : null,
        'iso_code' => $city->subdivisions ? $city->subdivisions[0]->isoCode : null,
    ],
    'location' => [
        'latitude' => $city->location->latitude,
        'longitude' => $city->location->latitude
    ],
    'zip_code' => $city->postal->code,
    'time_zone' => $city->location->timeZone,
    'metro_code' => $city->location->metroCode
];
$flatAttr = flattenArrayKeys($attr);

// Select response formatter
$format = null;
if (!empty($_GET['format'])) {
    $format = strtolower($_GET['format']);
    if (!in_array($_GET['format'], ['plain', 'json'])) {
        http_response_code(400);
        echo "ERROR: `format` attribute `$format` not found.";
        exit(1);
    }
}

// Select single attribute response
$what = null;
if (!empty($_GET['what'])) {
    $what = strtolower($_GET['what']);
    if (!array_key_exists($what, $attr + $flatAttr)) {
        http_response_code(400);
        echo "ERROR: `what` attribute `{$what}` not found.";
        exit(1);
    }
}

$response = $what ? insensitiveKeyValue($what, $attr + $flatAttr) : $attr;

if ($format === 'json') {
    header('Content-type: application/json; charset=utf-8');

    echo json_encode($response);
}
else {
    header('Content-Type: text/plain; charset=utf-8');

    if (is_array($response)) {
        foreach (flattenArrayKeys($response) as $key => $value) {
            echo prettyCase($key) . ': '. ($value ?: 'N/A') . PHP_EOL;
        }
    }
    else {
        echo $response;
    }
}
