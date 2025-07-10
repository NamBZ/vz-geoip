<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GeoIP Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database providers and paths. Support for multiple providers:
    | - MaxMind GeoLite2 (maxmind)
    | - DB-IP Lite (dbip)
    |
    */
    'provider' => env('GEOIP_PROVIDER', 'maxmind'), // maxmind, dbip

    'providers' => [
        'maxmind' => [
            'city_database' => storage_path('geoip/maxmind/GeoLite2-City.mmdb'),
            'asn_database' => storage_path('geoip/maxmind/GeoLite2-ASN.mmdb'),
            'country_database' => storage_path('geoip/maxmind/GeoLite2-Country.mmdb'),
            'name' => 'MaxMind GeoLite2',
            'website' => 'https://dev.maxmind.com/geoip/geolite2-free-geolocation-data',
        ],
        'dbip' => [
            'city_database' => storage_path('geoip/dbip/dbip-city-lite.mmdb'),
            'asn_database' => storage_path('geoip/dbip/dbip-asn-lite.mmdb'),
            'country_database' => storage_path('geoip/dbip/dbip-country-lite.mmdb'),
            'name' => 'DB-IP Lite',
            'website' => 'https://db-ip.com/db/download/ip-to-city-lite',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Output Format
    |--------------------------------------------------------------------------
    |
    | This value is the default output format when none is specified.
    | Supported formats: json, xml, csv, yaml
    |
    */
    'default_format' => 'json',

    /*
    |--------------------------------------------------------------------------
    | Supported Output Formats
    |--------------------------------------------------------------------------
    |
    | This array contains all supported output formats for the API.
    |
    */
    'supported_formats' => ['json', 'xml', 'csv', 'yaml'],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache settings for GeoIP lookups to improve performance.
    |
    */
    'cache' => [
        'enabled' => env('GEOIP_CACHE_ENABLED', true), // Enable or disable caching
        'ttl' => env('GEOIP_CACHE_TIMEOUT', 3600), // Cache time-to-live in seconds
        'prefix' => 'geoip_',
    ],
];
