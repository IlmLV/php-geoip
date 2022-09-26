[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/captainhook/captainhook/license.svg?v=1)](https://packagist.org/packages/captainhook/captainhook)
![GitHub Repo stars](https://img.shields.io/github/stars/IlmLV/php-geoip)

# PHP GeoIP
Simple PHP script for GeoIP database reader and formatting response.

## Usage
Designed to be used as individual web service.

GET /
```yaml
IP: 89.111.23.112
Organisation: SIA Digitalas Ekonomikas Attistibas Centrs
City-Name: N/A
Country-Name: Latvia
Country-Iso-Code: LV
Country-Is-In-European-Union: 1
Country-Flag-Emoji: 🇱🇻
Country-Flag-Url: //ip.serviss.it/images/flags/lv.svg
Continent-Name: Europe
Continent-Code: EU
Region-Name: N/A
Region-Iso-Code: N/A
Location-Latitude: 57
Location-Longitude: 57
Zip-Code: N/A
Time-Zone: Europe/Riga
Metro-Code: N/A
```

It is possible to request only single attribute response.

GET /?what=ip
```
89.111.23.112
```

It is possible to format response as json.

GET /?format=json
```
{
   "ip" : "89.111.23.112",
   "organisation" : "SIA Digitalas Ekonomikas Attistibas Centrs",
   "city" : {
      "name" : null
   },
   "country" : {
      "is_in_european_union" : true,
      "iso_code" : "LV",
      "name" : "Latvia",
      "flag" : {
         "emoji" : "🇱🇻",
         "url" : "//ip.serviss.it/images/flags/lv.svg"
      }
   },
   "continent" : {
      "code" : "EU",
      "name" : "Europe"
   },
   "region" : {
      "iso_code" : null,
      "name" : null
   },
   "location" : {
      "latitude" : 57,
      "longitude" : 57
   },
   "zip_code" : null,
   "time_zone" : "Europe/Riga",
   "metro_code" : null
}
```

## Limitations
Currently only supports HTTP requests, no CLI support.

## License
[MIT](https://choosealicense.com/licenses/mit/)
