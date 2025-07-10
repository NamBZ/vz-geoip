<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $this->getApiKeyFromRequest($request);
        $validApiKey = env('GEOIP_ADMIN_API_KEY');

        // Check if API key is configured
        if (!$validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Admin API key not configured on server',
                'code' => 500
            ], 500);
        }

        // Check if API key is provided
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key required. Provide via X-API-Key header, api_key parameter, or Authorization Bearer token',
                'code' => 401,
                'help' => [
                    'header' => 'X-API-Key: your-api-key',
                    'parameter' => '?api_key=your-api-key',
                    'bearer' => 'Authorization: Bearer your-api-key'
                ]
            ], 401);
        }

        // Validate API key
        if (!hash_equals($validApiKey, $apiKey)) {
            // Log failed attempt
            \Illuminate\Support\Facades\Log::warning('Invalid API key attempt for admin endpoint', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'provided_key' => substr($apiKey, 0, 8) . '...' // Log only first 8 chars for security
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
                'code' => 403
            ], 403);
        }

        // Log successful authentication
        \Illuminate\Support\Facades\Log::info('API key authenticated for admin endpoint', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path()
        ]);

        return $next($request);
    }

    /**
     * Get API key from request
     *
     * @param Request $request
     * @return string|null
     */
    private function getApiKeyFromRequest(Request $request)
    {
        // Try X-API-Key header first
        if ($request->hasHeader('X-API-Key')) {
            return $request->header('X-API-Key');
        }

        // Try Authorization Bearer token
        if ($request->hasHeader('Authorization')) {
            $auth = $request->header('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }

        // Try query parameter
        if ($request->has('api_key')) {
            return $request->input('api_key');
        }

        // Try form data
        if ($request->has('api_key')) {
            return $request->input('api_key');
        }

        return null;
    }
}
