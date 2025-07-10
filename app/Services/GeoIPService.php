<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeoIPService
{
    private $reader;
    private $asnReader;
    private $cacheEnabled;
    private $cacheTtl;
    private $cachePrefix;

    public function __construct()
    {
        $databasePath = config('geoip.database_path');
        $asnDatabasePath = config('geoip.asn_database_path');

        if (!file_exists($databasePath)) {
            throw new Exception("GeoIP database file not found: {$databasePath}");
        }

        if (!file_exists($asnDatabasePath)) {
            throw new Exception("ASN database file not found: {$asnDatabasePath}");
        }

        try {
            $this->reader = new Reader($databasePath);
            $this->asnReader = new Reader($asnDatabasePath);
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
            $asnData = $this->getASNData($ip);

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
                'organization' => $asnData['organization'] ?? $this->getOrganization($record),
                'asn' => $asnData['asn'] ?? null,
                'asn_organization' => $asnData['asn_organization'] ?? null,
                'isp' => $asnData['organization'] ?? null,
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
     * Get ASN (Autonomous System Number) information for an IP address
     *
     * @param string $ip
     * @return array
     */
    private function getASNData($ip)
    {
        try {
            $asnRecord = $this->asnReader->asn($ip);

            return [
                'asn' => $asnRecord->autonomousSystemNumber ?? null,
                'asn_organization' => $asnRecord->autonomousSystemOrganization ?? null,
                'organization' => $asnRecord->autonomousSystemOrganization ?? null,
            ];
        } catch (AddressNotFoundException $e) {
            // ASN data not found, return null values
            return [
                'asn' => null,
                'asn_organization' => null,
                'organization' => null,
            ];
        } catch (Exception $e) {
            // Other errors, return null values
            return [
                'asn' => null,
                'asn_organization' => null,
                'organization' => null,
            ];
        }
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

    /**
     * Get database statistics and metadata
     *
     * @return array
     */
    public function getDatabaseStats()
    {
        try {
            $cityStats = $this->getDatabaseInfo($this->reader, 'GeoLite2-City');
            $asnStats = $this->getDatabaseInfo($this->asnReader, 'GeoLite2-ASN');

            return [
                'databases' => [
                    'city' => $cityStats,
                    'asn' => $asnStats
                ],
                'total_records' => $cityStats['record_count'] + $asnStats['record_count'],
                'last_updated' => max($cityStats['build_date'], $asnStats['build_date']),
                'api_version' => '1.0.0',
                'generated_at' => date('c'),
            ];
        } catch (Exception $e) {
            throw new Exception("Error retrieving database statistics: " . $e->getMessage());
        }
    }

    /**
     * Get information about a specific database
     *
     * @param Reader $reader
     * @param string $dbName
     * @return array
     */
    private function getDatabaseInfo(Reader $reader, string $dbName)
    {
        $metadata = $reader->metadata();

        return [
            'database_name' => $dbName,
            'database_type' => $metadata->databaseType ?? 'Unknown',
            'record_count' => $metadata->nodeCount ?? 0,
            'build_date' => date('c', $metadata->buildEpoch ?? 0),
            'description' => $metadata->description['en'] ?? 'No description',
            'binary_format_major_version' => $metadata->binaryFormatMajorVersion ?? 0,
            'binary_format_minor_version' => $metadata->binaryFormatMinorVersion ?? 0,
            'ip_version' => $metadata->ipVersion ?? 0,
            'node_byte_size' => $metadata->nodeByteSize ?? 0,
            'search_tree_size' => $metadata->searchTreeSize ?? 0,
        ];
    }
}
