<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 100, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        // Get current attempts count
        $attempts = Cache::get($key, 0);

        // Check if rate limit exceeded
        if ($attempts >= $maxAttempts) {
            return $this->buildRateLimitResponse($request, $maxAttempts, $decayMinutes, $attempts);
        }

        // Increment attempts counter
        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $decayMinutes * 60);
        }

        $response = $next($request);

        // Add rate limit headers to response
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $this->getTimeUntilNextRetry($key)
        );
    }

    /**
     * Resolve request signature for rate limiting
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request)
    {
        // Use IP address as the key
        $ip = $request->ip();

        // Add endpoint to make limits per endpoint
        $route = $request->getPathInfo();

        return 'rate_limit:' . sha1($ip . '|' . $route);
    }

    /**
     * Build rate limit exceeded response
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @param  int  $currentAttempts
     * @return \Illuminate\Http\Response
     */
    protected function buildRateLimitResponse(Request $request, $maxAttempts, $decayMinutes, $currentAttempts)
    {
        $retryAfter = $this->getTimeUntilNextRetry($this->resolveRequestSignature($request));

        $error = [
            'error' => true,
            'message' => 'Rate limit exceeded. Too many requests.',
            'code' => 429,
            'details' => [
                'max_attempts' => $maxAttempts,
                'current_attempts' => $currentAttempts,
                'time_window' => $decayMinutes . ' minute(s)',
                'retry_after' => $retryAfter . ' seconds',
                'reset_time' => Carbon::now()->addSeconds($retryAfter)->toISOString()
            ]
        ];

        // Determine response format based on request
        $format = $this->getRequestedFormat($request);

        switch ($format) {
            case 'xml':
                $content = $this->toXml($error);
                $contentType = 'application/xml';
                break;

            case 'csv':
                $content = $this->toCsv($error);
                $contentType = 'text/csv';
                break;

            case 'yaml':
                $content = $this->toYaml($error);
                $contentType = 'application/x-yaml';
                break;

            case 'json':
            default:
                $content = json_encode($error, JSON_PRETTY_PRINT);
                $contentType = 'application/json';
                break;
        }

        return response($content, 429)
            ->header('Content-Type', $contentType)
            ->header('X-RateLimit-Limit', $maxAttempts)
            ->header('X-RateLimit-Remaining', 0)
            ->header('X-RateLimit-Reset', Carbon::now()->addSeconds($retryAfter)->timestamp)
            ->header('Retry-After', $retryAfter)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    /**
     * Add rate limit headers to response
     *
     * @param  \Illuminate\Http\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int  $retryAfter
     * @return \Illuminate\Http\Response
     */
    protected function addHeaders($response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remainingAttempts));

        if ($retryAfter !== null) {
            $response->headers->set('X-RateLimit-Reset', Carbon::now()->addSeconds($retryAfter)->timestamp);
        }

        return $response;
    }

    /**
     * Calculate remaining attempts
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts)
    {
        $attempts = Cache::get($key, 0);
        return $maxAttempts - $attempts;
    }

    /**
     * Get time until next retry in seconds
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        // Since we can't easily get TTL from cache, 
        // return remaining time based on cache existence
        if (Cache::has($key)) {
            return 60; // Return 1 minute as default
        }

        return 0;
    }

    /**
     * Get requested format from request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getRequestedFormat(Request $request)
    {
        // Check query parameter first
        $format = $request->query('format');
        if ($format && in_array($format, config('geoip.supported_formats'))) {
            return $format;
        }

        // Check Accept header
        $acceptHeader = $request->header('Accept');
        if ($acceptHeader) {
            if (strpos($acceptHeader, '*/*') !== false || strpos($acceptHeader, 'text/html') !== false) {
                return 'json';
            }
            if (strpos($acceptHeader, 'application/xml') !== false || strpos($acceptHeader, 'text/xml') !== false) {
                return 'xml';
            }
            if (strpos($acceptHeader, 'text/csv') !== false) {
                return 'csv';
            }
            if (strpos($acceptHeader, 'application/x-yaml') !== false || strpos($acceptHeader, 'text/yaml') !== false) {
                return 'yaml';
            }
        }

        return config('geoip.default_format', 'json');
    }

    /**
     * Convert array to XML format
     *
     * @param array $data
     * @return string
     */
    private function toXml(array $data)
    {
        $xml = new \SimpleXMLElement('<error/>');
        $this->arrayToXml($data, $xml);
        return $xml->asXML();
    }

    /**
     * Recursively convert array to XML
     *
     * @param array $data
     * @param \SimpleXMLElement $xml
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value ?? ''));
            }
        }
    }

    /**
     * Convert array to CSV format
     *
     * @param array $data
     * @return string
     */
    private function toCsv(array $data)
    {
        // Flatten nested arrays for CSV
        $flattened = [];
        $this->flattenArray($data, $flattened);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($flattened));
        fputcsv($output, array_values($flattened));
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Flatten nested array for CSV
     *
     * @param array $array
     * @param array &$result
     * @param string $prefix
     */
    private function flattenArray(array $array, array &$result, $prefix = '')
    {
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '_' . $key : $key;

            if (is_array($value)) {
                $this->flattenArray($value, $result, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }
    }

    /**
     * Convert array to YAML format
     *
     * @param array $data
     * @return string
     */
    private function toYaml(array $data)
    {
        if (class_exists('Symfony\Component\Yaml\Yaml')) {
            return \Symfony\Component\Yaml\Yaml::dump($data, 2, 2);
        }

        // Fallback to simple YAML format
        return $this->arrayToSimpleYaml($data);
    }

    /**
     * Convert array to simple YAML format
     *
     * @param array $data
     * @param int $indent
     * @return string
     */
    private function arrayToSimpleYaml(array $data, $indent = 0)
    {
        $yaml = '';
        $spacing = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $yaml .= $spacing . $key . ":\n";
                $yaml .= $this->arrayToSimpleYaml($value, $indent + 1);
            } else {
                $yaml .= $spacing . $key . ': ' . (is_null($value) ? 'null' : $value) . "\n";
            }
        }

        return $yaml;
    }
}
