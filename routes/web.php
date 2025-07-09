<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return [
        'name' => 'GeoIP API',
        'version' => $router->app->version(),
        'rate_limiting' => [
            'limit' => '100 requests per minute per IP address',
            'headers' => [
                'X-RateLimit-Limit' => 'Maximum requests allowed',
                'X-RateLimit-Remaining' => 'Requests remaining in current window',
                'X-RateLimit-Reset' => 'Unix timestamp when rate limit resets'
            ]
        ],
        'endpoints' => [
            'GET /geoip' => 'Get GeoIP information for any IP address',
            'GET /geoip/ipv4' => 'Get GeoIP information for IPv4 address only',
            'GET /geoip/ipv6' => 'Get GeoIP information for IPv6 address only',
        ],
        'parameters' => [
            'ip' => 'IP address to lookup (optional, defaults to client IP)',
            'format' => 'Output format: json, xml, csv, yaml (optional, defaults to json)',
            'callback' => 'JSONP callback function name (optional, JSON format only)',
        ],
        'examples' => [
            '/geoip?ip=8.8.8.8',
            '/geoip?ip=8.8.8.8&format=xml',
            '/geoip/ipv4?ip=8.8.8.8&callback=myCallback',
        ]
    ];
});

// GeoIP API Routes with Rate Limiting (100 requests per minute per IP)
$router->group(['prefix' => 'geoip', 'middleware' => 'throttle:100,1'], function () use ($router) {
    // General GeoIP endpoint (supports both IPv4 and IPv6)
    $router->get('/', 'GeoIPController@getGeoIP');

    // IPv4 specific endpoint
    $router->get('/ipv4', 'GeoIPController@getGeoIPv4');

    // IPv6 specific endpoint
    $router->get('/ipv6', 'GeoIPController@getGeoIPv6');
});
