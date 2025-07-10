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
            'GET /geoip/stats' => 'Get database statistics and information',
            'GET /geoip/health' => 'API health check and basic info',
            'GET /geoip/providers' => 'Get available providers and current provider info',
            'POST /geoip/switch-provider' => 'Switch to a different provider',
            'POST /geoip/update' => 'Update GeoIP databases (maxmind, dbip, or all)',
        ],
        'parameters' => [
            'ip' => 'IP address to lookup (optional, defaults to client IP)',
            'format' => 'Output format: json, xml, csv, yaml (optional, defaults to json)',
            'callback' => 'JSONP callback function name (optional, JSON format only)',
            'provider' => 'Provider to use: maxmind, dbip (optional, can be used per request or globally)',
        ],
        'examples' => [
            '/geoip?ip=8.8.8.8',
            '/geoip?ip=8.8.8.8&format=xml',
            '/geoip?ip=8.8.8.8&provider=dbip',
            '/geoip/ipv4?ip=8.8.8.8&callback=myCallback',
            '/geoip/stats',
            '/geoip/health',
            '/geoip/providers',
            '/geoip/switch-provider?provider=dbip',
            'POST /geoip/update?provider=dbip',
            'POST /geoip/update?provider=all&force=1&no-backup=1',
        ]
    ];
});

// Admin redirect
$router->get('/admin', function () {
    return redirect('/admin.html');
});

// GeoIP API Routes with Rate Limiting (100 requests per minute per IP)
$router->group(['prefix' => 'geoip', 'middleware' => 'throttle:100,1'], function () use ($router) {
    // General GeoIP endpoint (supports both IPv4 and IPv6)
    $router->get('/', 'GeoIPController@getGeoIP');

    // IPv4 specific endpoint
    $router->get('/ipv4', 'GeoIPController@getGeoIPv4');

    // IPv6 specific endpoint
    $router->get('/ipv6', 'GeoIPController@getGeoIPv6');

    // Database statistics endpoint
    $router->get('/stats', 'GeoIPController@getDatabaseStats');

    // Health check endpoint
    $router->get('/health', 'GeoIPController@getHealthCheck');

    // Provider management endpoints
    $router->get('/providers', 'GeoIPController@getProviders');
    $router->get('/switch-provider', 'GeoIPController@switchProvider');
    $router->post('/switch-provider', 'GeoIPController@switchProvider');
});

// GeoIP Admin Routes with API Key Protection and Rate Limiting
$router->group([
    'prefix' => 'geoip',
    'middleware' => ['throttle:10,1', 'apikey']
], function () use ($router) {
    // Database update endpoint (protected by API key)
    $router->post('/update', 'GeoIPController@updateDatabase');
    $router->get('/update', 'GeoIPController@updateDatabase');
});
