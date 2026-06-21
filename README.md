<h1 align="center">PHP GeoIP</h1>

<p align="center">
  A lightweight PHP library for IP geolocation lookups and response formatting —
  one API across a zero-config remote service, MaxMind GeoLite2, DB-IP, IPLocate,
  IPinfo and IP2Location.
</p>

<p align="center">
  <a href="https://packagist.org/packages/ilmlv/php-geoip"><img src="https://img.shields.io/packagist/v/ilmlv/php-geoip.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/ilmlv/php-geoip"><img src="https://img.shields.io/packagist/dt/ilmlv/php-geoip.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://php.net/"><img src="https://img.shields.io/packagist/php-v/ilmlv/php-geoip.svg?style=flat-square" alt="PHP Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/ilmlv/php-geoip.svg?style=flat-square" alt="License"></a>
</p>

---

## Features

- **Zero config by default** — works out of the box via the remote `ip.serviss.it` service, no database to download.
- **Simple API** — resolve an IP to a structured array with a single `locate()` call.
- **Multiple data sources** — the remote service, MaxMind GeoLite2, DB-IP, IPLocate, IPinfo (MMDB) and IP2Location (BIN), behind one provider interface.
- **City & ASN data** — country, region, city, coordinates, timezone, and the originating organisation.
- **Flexible output** — render results as JSON or plain text, or pluck a single attribute.
- **Command line ready** — a `geoip` binary for lookups straight from the shell.
- **License-clean** — ships no database; you supply your own for local lookups, and the library surfaces each source's required attribution.
- **Lean dependencies** — only the official `geoip2/geoip2` reader. PHP 7.2+.

## Table of contents

- [Installation](#installation)
- [Obtaining a database](#obtaining-a-database)
- [Usage](#usage)
- [Database providers](#database-providers)
- [Result schema](#result-schema)
- [Error handling](#error-handling)
- [Command line](#command-line)
- [Examples](#examples)
- [Requirements](#requirements)
- [License & attribution](#license--attribution)

## Installation

```bash
composer require ilmlv/php-geoip
```

## Obtaining a database

You don't need one to get started: by default the library queries the remote
**ip.serviss.it** service (see [Database providers](#database-providers)). For
local, offline or high-volume lookups, use one of the database providers instead.

This package **bundles no database** — database licenses vary by source and most
require keeping the data current. The included `geoip-update` downloader supports
several sources:

```bash
# MaxMind GeoLite2 (free account + license key required)
MAXMIND_LICENSE_KEY=your_key vendor/bin/geoip-update --dir=./data

# DB-IP Lite — no API key, CC BY 4.0 (just attribute db-ip.com)
vendor/bin/geoip-update --source=dbip --dir=./data

# Any direct .mmdb / .mmdb.gz URL (e.g. an IPLocate or IPinfo tokenised link)
vendor/bin/geoip-update --source=url --url='https://…/file.mmdb.gz' --dir=./data
```

For scheduled MaxMind updates, the official
[`geoipupdate`](https://github.com/maxmind/geoipupdate) tool is also a good option.
See [Database providers](#database-providers) for which sources need a key and how
to attribute them.

## Usage

```php
use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\ResponseFormatter;

// Zero config: no argument → looks up via the remote ip.serviss.it service.
$locator = new GeoIpLocator();

// Or read a local MaxMind GeoLite2 (or compatible) database:
$locator = new GeoIpLocator(
    '/path/to/GeoLite2-City.mmdb',
    '/path/to/GeoLite2-ASN.mmdb'   // optional — enables the `organisation` field
);

$result = $locator->locate('89.111.23.112');

$formatter = new ResponseFormatter();
echo $formatter->toJson($result);                   // JSON string
echo $formatter->toPlainText($result);              // "Pretty-Key: value" lines
echo $formatter->pluck($result, 'country-iso-code'); // "LV"
```

To include a `country.flag.url` in the result, pass a base URL as the third
constructor argument:

```php
$locator = new GeoIpLocator($cityDb, $asnDb, '//cdn.example.com/flags');
```

## Database providers

By default (no constructor argument) the library uses the **remote** provider,
which queries the public `ip.serviss.it` service — no database required. To read
a local database instead, pass a provider to `GeoIpLocator::withProvider()`.
Every provider returns the same [result schema](#result-schema), so the rest of
your code is unchanged.

```php
use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\Provider\ServissItProvider;
use IlmLV\GeoIp\Provider\MmdbCityProvider;
use IlmLV\GeoIp\Provider\IpinfoLiteProvider;
use IlmLV\GeoIp\Provider\IP2LocationProvider;

// Remote ip.serviss.it (default; equivalent to `new GeoIpLocator()`)
GeoIpLocator::withProvider(new ServissItProvider());

// MMDB family (MaxMind-reader compatible). Optional 2nd arg = ASN database.
GeoIpLocator::withProvider(MmdbCityProvider::dbip('data/dbip-city-lite.mmdb'));
GeoIpLocator::withProvider(MmdbCityProvider::iplocate('data/iplocate-city.mmdb'));
GeoIpLocator::withProvider(MmdbCityProvider::maxmind('data/GeoLite2-City.mmdb', 'data/GeoLite2-ASN.mmdb'));

// IPinfo Lite (flat MMDB schema — country/continent/ASN only)
GeoIpLocator::withProvider(new IpinfoLiteProvider('data/ipinfo-lite.mmdb'));

// IP2Location BIN (needs ip2location/ip2location-php; see Requirements)
GeoIpLocator::withProvider(new IP2LocationProvider('data/IP2LOCATION-LITE-DB11.BIN'));

$locator->attribution(); // the source's required attribution string, or null
```

| Source | Format | API key | License | Fields |
| ------ | ------ | ------- | ------- | ------ |
| **ip.serviss.it** (default) | remote HTTP | no | — (depends on the service's source) | per the service (city, region, country, coords, tz, ASN) |
| **MaxMind GeoLite2** | MMDB | yes | CC BY-SA 4.0 | city, region, country, coords, tz, ASN |
| **DB-IP Lite** | MMDB | no | CC BY 4.0 † | city, region, country, coords |
| **IPLocate.io** | MMDB | yes (free) | CC BY-SA 4.0 † | city, region, country, coords, ASN |
| **IPinfo Lite** | MMDB | yes (token) | CC BY-SA 4.0 † | country, continent, ASN |
| **IP2Location LITE** | BIN | yes (free) | CC BY-SA 4.0 † | city, region, country, coords, tz |

The **remote provider** makes an outbound HTTPS call per lookup to a fixed
endpoint (`https://ip.serviss.it`) and needs no database; it's ideal for getting
started and low-volume use. For offline use, high volume, or to avoid sending IPs
to a third party, choose a local provider. It throws `RemoteException` on a
network/service failure.

† These sources require their attribution to be displayed wherever their data
appears. `GeoIpLocator::attribution()` returns the exact snippet (the CLI prints
it automatically; the web-endpoint [example](#examples) shows how to render it).
See [License & attribution](#license--attribution).

## Result schema

`locate()` returns a normalised array. Missing values are `null`.

```php
[
    'ip'           => '89.111.23.112',
    'organisation' => 'SIA Digitalas Ekonomikas Attistibas Centrs',
    'city'         => ['name' => null],
    'country'      => [
        'name'                 => 'Latvia',
        'iso_code'             => 'LV',
        'is_in_european_union' => true,
        'flag'                 => ['emoji' => '🇱🇻'],
    ],
    'continent'    => ['name' => 'Europe', 'code' => 'EU'],
    'region'       => ['name' => null, 'iso_code' => null],
    'location'     => ['latitude' => 57.0, 'longitude' => 24.1],
    'zip_code'     => null,
    'time_zone'    => 'Europe/Riga',
    'metro_code'   => null,
]
```

`ResponseFormatter::pluck()` accepts any nested or flattened key, case- and
separator-insensitive — `country-iso-code`, `country_iso_code`, and `country`
all resolve.

## Error handling

All exceptions implement the `IlmLV\GeoIp\Exception\GeoIpException` marker
interface, so the whole package can be caught in one block.

| Condition                          | Exception                  |
| ---------------------------------- | -------------------------- |
| Malformed IP address               | `InvalidIpException`       |
| IP absent from the database        | `AddressNotFoundException` |
| Unknown attribute in `pluck()`     | `UnknownAttributeException`|
| Missing optional provider package  | `MissingDependencyException` |
| Remote provider network/service error | `RemoteException`       |

## Command line

The package installs a `geoip` binary at `vendor/bin/geoip` for lookups from the
shell. With no `--provider`, it uses the remote `ip.serviss.it` service (zero setup):

```bash
vendor/bin/geoip 89.111.23.112
vendor/bin/geoip 89.111.23.112 --format=json
vendor/bin/geoip 89.111.23.112 --what=country-iso-code   # → LV
```

For a local database, pick a provider:

```bash
vendor/bin/geoip 89.111.23.112 --provider=mmdb --db=./data/GeoLite2-City.mmdb
vendor/bin/geoip 89.111.23.112 --provider=dbip --db=./data/dbip-city-lite.mmdb
```

| Option              | Description                                                        |
| ------------------- | ------------------------------------------------------------------ |
| `--format=`         | `plain` (default) or `json`.                                       |
| `--what=`           | Print a single attribute, e.g. `country-iso-code`.                 |
| `--provider=`       | `serviss` (default, remote), `mmdb`, `dbip`, `iplocate`, `ipinfo`, `ip2location` (or `$GEOIP_PROVIDER`). |
| `--db=`             | Primary database path (or `--city-db` / `$GEOIP_CITY_DB`).         |
| `--asn-db=`         | ASN `.mmdb` for `organisation` (or `$GEOIP_ASN_DB`).               |
| `--ip2location=`    | Path to an IP2Location `.BIN` (with `--provider=ip2location`).     |
| `--flag-base-url=`  | Base URL for flag SVGs; adds `country.flag.url`.                   |
| `-h`, `--help`      | Show usage.                                                        |

Database paths default to `./data`. Exit codes: `0` success, `1` usage/runtime
error, `2` IP not found.

## Examples

Short, copy-pasteable recipes. They use the zero-config remote provider unless a
database path is given.

### Look up an IP

```php
use IlmLV\GeoIp\GeoIpLocator;

$result = (new GeoIpLocator())->locate('89.111.23.112');
echo $result['country']['name'];      // "Latvia"
echo $result['country']['iso_code'];  // "LV"
echo $result['time_zone'];            // "Europe/Riga"
```

### Format the result

```php
use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\ResponseFormatter;

$result = (new GeoIpLocator())->locate('89.111.23.112');
$format = new ResponseFormatter();

echo $format->toJson($result);                    // JSON string
echo $format->toPlainText($result);               // "Country-Name: Latvia\n…"
echo $format->pluck($result, 'country-iso-code'); // "LV"  (single field)
```

### Read a local database

```php
use IlmLV\GeoIp\GeoIpLocator;

// MaxMind GeoLite2 (City + optional ASN). Download with `vendor/bin/geoip-update`.
$result = (new GeoIpLocator('data/GeoLite2-City.mmdb', 'data/GeoLite2-ASN.mmdb'))
    ->locate('89.111.23.112');
```

```php
use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\Provider\MmdbCityProvider;

// DB-IP Lite (no API key). `attribution()` then returns the link you must show.
$locator = GeoIpLocator::withProvider(MmdbCityProvider::dbip('data/dbip-city-lite.mmdb'));
$result  = $locator->locate('89.111.23.112');
echo $locator->attribution(); // <a href='https://db-ip.com'>IP Geolocation by DB-IP</a>
```

### Handle errors

```php
use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\Exception\GeoIpException;

try {
    $result = (new GeoIpLocator())->locate($ip);
} catch (GeoIpException $e) {
    // Covers invalid IP, address-not-found and remote failures in one catch.
    echo 'Lookup failed: ' . $e->getMessage();
}
```

### A complete web endpoint

Save as `public/index.php` and run `php -S localhost:8080 -t public`. It supports
`?ip=`, `?format=json|plain` (default `plain`) and `?what=` for a single field:

```
GET /?ip=89.111.23.112                       → plain text
GET /?ip=89.111.23.112&format=json           → JSON
GET /?ip=89.111.23.112&what=country-iso-code → "LV"
```

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use IlmLV\GeoIp\GeoIpLocator;
use IlmLV\GeoIp\ResponseFormatter;
use IlmLV\GeoIp\Exception\GeoIpException;

$ip     = $_GET['ip']     ?? ($_SERVER['REMOTE_ADDR'] ?? '');
$format = $_GET['format'] ?? 'plain';
$what   = $_GET['what']   ?? null;

$formatter = new ResponseFormatter();

try {
    $result = (new GeoIpLocator())->locate($ip);
    $value  = $what !== null ? $formatter->pluck($result, $what) : $result;
} catch (GeoIpException $e) {
    // Covers invalid IP, not-found, unknown attribute and remote failures.
    http_response_code(400);
    exit('ERROR: ' . $e->getMessage());
}

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo is_array($value) ? $formatter->toJson($value) : json_encode($value);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo is_array($value) ? $formatter->toPlainText($value) : $value;
}
```

Swap `new GeoIpLocator()` for any provider to serve from a local database.

## Requirements

- PHP **7.2** or newer
- Extensions: `json`, `mbstring`
- For the default remote provider: outbound HTTPS and either `allow_url_fopen`
  or `ext-curl`. No database needed.
- For local providers: a geolocation database from any [supported provider](#database-providers)
- For the IP2Location provider only: the
  [`ip2location/ip2location-php`](https://packagist.org/packages/ip2location/ip2location-php)
  package (`composer require ip2location/ip2location-php`, needs `ext-bcmath`).
  The provider throws a clear `MissingDependencyException` if it's absent.

## License & attribution

The **code** in this repository is released under the [MIT License](LICENSE).

**Database data is never bundled** and carries its own license. You must obtain a
database under that source's terms, and several require visible attribution
wherever their data is shown. `GeoIpLocator::attribution()` returns the exact
snippet for the active source.

| Source | License | Required attribution |
| ------ | ------- | -------------------- |
| MaxMind GeoLite2 | [GeoLite2 EULA](https://www.maxmind.com/en/geolite2/eula) (CC BY-SA 4.0) | *This product includes GeoLite Data created by MaxMind, available from [maxmind.com](https://www.maxmind.com).* |
| DB-IP Lite | [CC BY 4.0](https://db-ip.com/db/download/ip-to-city-lite) | `<a href='https://db-ip.com'>IP Geolocation by DB-IP</a>` |
| IPLocate.io | [CC BY-SA 4.0](https://www.iplocate.io/free-databases) | Attribute IPLocate.io |
| IPinfo Lite | [IPinfo terms](https://ipinfo.io) | Attribute IPinfo |
| IP2Location LITE | [CC BY-SA 4.0](https://lite.ip2location.com) | Attribute IP2Location LITE |
