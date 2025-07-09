<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeoIPService
{
    private $reader;
    private $cacheEnabled;
    private $cacheTtl;
    private $cachePrefix;

    public function __construct()
    {
        $databasePath = config('geoip.database_path');

        if (!file_exists($databasePath)) {
            throw new Exception("GeoIP database file not found: {$databasePath}");
        }

        try {
            $this->reader = new Reader($databasePath);
        } catch (Exception $e) {
            throw new Exception("Invalid GeoIP database: " . $e->getMessage());
        }

        $this->cacheEnabled = config('geoip.cache.enabled', true);
        $this->cacheTtl = config('geoip.cache.ttl', 3600);
        $this->cachePrefix = config('geoip.cache.prefix', 'geoip_');
    }

    /**
     * Get GeoIP information for an IP address
     *
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function getGeoData($ip)
    {
        if (!$this->isValidIP($ip)) {
            throw new Exception("Invalid IP address: {$ip}");
        }

        $cacheKey = $this->cachePrefix . md5($ip);

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $record = $this->reader->city($ip);

            $data = [
                'ip' => $ip,
                'country_code' => $record->country->isoCode ?? null,
                'country' => $record->country->name ?? null,
                'region_code' => $record->mostSpecificSubdivision->isoCode ?? null,
                'region' => $record->mostSpecificSubdivision->name ?? null,
                'city' => $record->city->name ?? null,
                'postal_code' => $record->postal->code ?? null,
                'continent_code' => $record->continent->code ?? null,
                'latitude' => $record->location->latitude ?? null,
                'longitude' => $record->location->longitude ?? null,
                'organization' => $this->getOrganization($record),
                'timezone' => $record->location->timeZone ?? null,
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $data, $this->cacheTtl);
            }

            return $data;
        } catch (AddressNotFoundException $e) {
            throw new Exception("IP address not found in database: {$ip}");
        } catch (Exception $e) {
            throw new Exception("Error retrieving GeoIP data: " . $e->getMessage());
        }
    }

    /**
     * Validate IP address
     *
     * @param string $ip
     * @return bool
     */
    public function isValidIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if IP is IPv4
     *
     * @param string $ip
     * @return bool
     */
    public function isIPv4($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Check if IP is IPv6
     *
     * @param string $ip
     * @return bool
     */
    public function isIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Get organization information from ASN data
     *
     * @param $record
     * @return string|null
     */
    private function getOrganization($record)
    {
        $org = '';

        if (isset($record->traits->autonomousSystemNumber)) {
            $org .= 'AS' . $record->traits->autonomousSystemNumber;
        }

        if (isset($record->traits->autonomousSystemOrganization)) {
            if ($org) {
                $org .= ' ';
            }
            $org .= $record->traits->autonomousSystemOrganization;
        }

        return $org ?: null;
    }

    /**
     * Get GeoIP data specifically for IPv4
     *
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function getGeoDataIPv4($ip)
    {
        if (!$this->isIPv4($ip)) {
            throw new Exception("Invalid IPv4 address: {$ip}");
        }

        return $this->getGeoData($ip);
    }

    /**
     * Get GeoIP data specifically for IPv6
     *
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function getGeoDataIPv6($ip)
    {
        if (!$this->isIPv6($ip)) {
            throw new Exception("Invalid IPv6 address: {$ip}");
        }

        return $this->getGeoData($ip);
    }
}
