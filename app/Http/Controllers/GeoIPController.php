<?php

namespace App\Http\Controllers;

use App\Services\GeoIPService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Yaml\Yaml;
use Exception;

class GeoIPController extends Controller
{
    private $geoIPService;

    public function __construct()
    {
        // Constructor sẽ khởi tạo service với provider mặc định
        // Provider có thể được thay đổi động thông qua switchProvider method
    }

    /**
     * Get GeoIP service instance with optional provider
     *
     * @param Request $request
     * @return GeoIPService
     */
    private function getGeoIPService(Request $request)
    {
        $provider = $request->query('provider');

        try {
            if ($provider) {
                return new GeoIPService($provider);
            }
            return new GeoIPService();
        } catch (Exception $e) {
            // If custom provider fails, fallback to default
            return new GeoIPService();
        }
    }

    /**
     * Get GeoIP information for any IP address
     *
     * @param Request $request
     * @return Response
     */
    public function getGeoIP(Request $request)
    {
        $ip = $request->query('ip', $request->ip());

        try {
            $geoIPService = $this->getGeoIPService($request);
            $data = $geoIPService->getGeoData($ip);
            return $this->formatResponse($request, $data);
        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        }
    }

    /**
     * Get GeoIP information for IPv4 address only
     *
     * @param Request $request
     * @return Response
     */
    public function getGeoIPv4(Request $request)
    {
        $ip = $request->query('ip', $request->ip());

        try {
            $geoIPService = $this->getGeoIPService($request);
            $data = $geoIPService->getGeoDataIPv4($ip);
            return $this->formatResponse($request, $data);
        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        }
    }

    /**
     * Get GeoIP information for IPv6 address only
     *
     * @param Request $request
     * @return Response
     */
    public function getGeoIPv6(Request $request)
    {
        $ip = $request->query('ip', $request->ip());

        try {
            $geoIPService = $this->getGeoIPService($request);
            $data = $geoIPService->getGeoDataIPv6($ip);
            return $this->formatResponse($request, $data);
        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        }
    }

    /**
     * Get database statistics and information
     *
     * @param Request $request
     * @return Response
     */
    public function getDatabaseStats(Request $request)
    {
        try {
            $geoIPService = $this->getGeoIPService($request);
            $stats = $geoIPService->getDatabaseStats();
            return $this->formatResponse($request, $stats);
        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Get API health check and basic info
     *
     * @param Request $request
     * @return Response
     */
    public function getHealthCheck(Request $request)
    {
        try {
            $geoIPService = $this->getGeoIPService($request);
            $stats = $geoIPService->getDatabaseStats();

            $health = [
                'status' => 'healthy',
                'api_version' => '1.0.0',
                'timestamp' => date('c'),
                'provider' => $geoIPService->getProviderInfo(),
                'databases' => [
                    'city_database' => [
                        'status' => 'online',
                        'records' => $stats['databases']['city']['record_count'],
                        'last_updated' => $stats['databases']['city']['build_date']
                    ],
                    'asn_database' => [
                        'status' => 'online',
                        'records' => $stats['databases']['asn']['record_count'],
                        'last_updated' => $stats['databases']['asn']['build_date']
                    ]
                ],
                'total_records' => $stats['total_records'],
                'uptime' => 'Service is operational'
            ];

            return $this->formatResponse($request, $health);
        } catch (Exception $e) {
            $health = [
                'status' => 'error',
                'api_version' => '1.0.0',
                'timestamp' => date('c'),
                'error' => $e->getMessage(),
                'databases' => [
                    'city_database' => ['status' => 'error'],
                    'asn_database' => ['status' => 'error']
                ]
            ];

            return $this->formatResponse($request, $health);
        }
    }

    /**
     * Get provider information
     *
     * @param Request $request
     * @return Response
     */
    public function getProviders(Request $request)
    {
        try {
            $geoIPService = $this->getGeoIPService($request);
            $data = $geoIPService->getProviderInfo();
            return $this->formatResponse($request, $data);
        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage(), 500);
        }
    }

    /**
     * Switch provider dynamically
     *
     * @param Request $request
     * @return Response
     */
    public function switchProvider(Request $request)
    {
        $provider = $request->query('provider');

        if (!$provider) {
            return $this->errorResponse($request, 'Provider parameter is required', 400);
        }

        try {
            $geoIPService = $this->getGeoIPService($request);
            $geoIPService->switchProvider($provider);
            $data = [
                'success' => true,
                'message' => "Provider switched to '{$provider}' successfully",
                'current_provider' => $geoIPService->getProviderInfo()
            ];
            return $this->formatResponse($request, $data);
        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage(), 400);
        }
    }

    /**
     * Format response based on requested format
     *
     * @param Request $request
     * @param array $data
     * @return Response
     */
    private function formatResponse(Request $request, array $data)
    {
        $format = $this->getRequestedFormat($request);
        $callback = $request->query('callback');

        switch ($format) {
            case 'xml':
                $content = $this->toXml($data);
                $contentType = 'application/xml';
                break;

            case 'csv':
                $content = $this->toCsv($data);
                $contentType = 'text/csv';
                break;

            case 'yaml':
                $content = $this->toYaml($data);
                $contentType = 'application/x-yaml';
                break;

            case 'json':
            default:
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $contentType = 'application/json';
                break;
        }

        // Handle JSONP callback for JSON format only
        if ($callback && $format === 'json') {
            $content = $this->wrapJsonp($callback, $content);
            $contentType = 'application/javascript';
        }

        return response($content, 200)
            ->header('Content-Type', $contentType)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    /**
     * Format error response
     *
     * @param Request $request
     * @param string $message
     * @param int $code
     * @return Response
     */
    private function errorResponse(Request $request, string $message, int $code = 400)
    {
        $format = $this->getRequestedFormat($request);
        $callback = $request->query('callback');

        $error = [
            'error' => true,
            'message' => $message,
            'code' => $code
        ];

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

        // Handle JSONP callback for JSON format only
        if ($callback && $format === 'json') {
            $content = $this->wrapJsonp($callback, $content);
            $contentType = 'application/javascript';
        }

        return response($content, $code)
            ->header('Content-Type', $contentType)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    /**
     * Determine the requested output format
     *
     * @param Request $request
     * @return string
     */
    private function getRequestedFormat(Request $request)
    {
        // Check query parameter first
        $format = $request->query('format');
        if ($format && in_array($format, config('geoip.supported_formats'))) {
            return $format;
        }

        // Check Accept header
        $acceptHeader = $request->header('Accept');
        if ($acceptHeader) {
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
        $xml = new \SimpleXMLElement('<geoip/>');
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
        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, array_keys($data));

        // Write data
        fputcsv($output, array_values($data));

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
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
            return Yaml::dump($data, 2, 2);
        }

        // Fallback to simple YAML format
        $yaml = '';
        foreach ($data as $key => $value) {
            $yaml .= $key . ': ' . (is_null($value) ? 'null' : $value) . "\n";
        }
        return $yaml;
    }

    /**
     * Wrap JSON content in JSONP callback
     *
     * @param string $callback
     * @param string $content
     * @return string
     */
    private function wrapJsonp(string $callback, string $content)
    {
        // Sanitize callback name to prevent XSS
        $callback = preg_replace('/[^a-zA-Z0-9_$]/', '', $callback);
        return $callback . '(' . $content . ');';
    }
}
