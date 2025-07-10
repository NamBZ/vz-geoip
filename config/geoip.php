<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GeoIP Database Paths
    |--------------------------------------------------------------------------
    |
    | These values are the paths to the GeoLite2 database files.
    |
    */
    'database_path' => storage_path('geoip/GeoLite2-City.mmdb'),
    'asn_database_path' => storage_path('geoip/GeoLite2-ASN.mmdb'),

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
        'enabled' => true,
        'ttl' => 3600, // 1 hour in seconds
        'prefix' => 'geoip_',
    ],
];
