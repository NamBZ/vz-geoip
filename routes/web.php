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

$router->get('/', function () {
    // Serve the main HTML file with headers
    return response()->file(app()->basePath('public/index.html'), [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
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
