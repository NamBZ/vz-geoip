<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class UpdateGeoIPCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geoip:update 
                            {provider=all : Provider to update (maxmind, dbip, or all)}
                            {--force : Force update even if files exist}
                            {--no-backup : Skip backup of existing databases}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update GeoIP databases from MaxMind and DB-IP';

    /**
     * Configuration
     */
    private $config = [
        'storage_dir' => 'geoip',
        'backup_dir' => 'geoip/backup',
        'maxmind_license_key' => null,
        'dbip_urls' => [
            'city' => 'https://download.db-ip.com/free/dbip-city-lite-{date}.mmdb.gz',
            'asn' => 'https://download.db-ip.com/free/dbip-asn-lite-{date}.mmdb.gz',
            'country' => 'https://download.db-ip.com/free/dbip-country-lite-{date}.mmdb.gz'
        ],
        'maxmind_databases' => ['GeoLite2-City', 'GeoLite2-ASN', 'GeoLite2-Country']
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $provider = $this->argument('provider');
        $force = $this->option('force');
        $noBackup = $this->option('no-backup');

        $this->info("🌍 Starting GeoIP database update for: {$provider}");
        $this->info("📁 Storage directory: " . storage_path($this->config['storage_dir']));

        try {
            $this->createDirectories();

            switch ($provider) {
                case 'maxmind':
                    $this->updateMaxMind2($force, $noBackup);
                    break;
                case 'dbip':
                    $this->updateDBIP($force, $noBackup);
                    break;
                case 'all':
                    $this->updateMaxMind2($force, $noBackup);
                    $this->updateDBIP($force, $noBackup);
                    break;
                default:
                    $this->error("❌ Invalid provider: {$provider}");
                    return 1;
            }

            $this->verifyDatabases();
            $this->cleanupOldBackups();
            $this->setPermissions();

            $this->info("✅ GeoIP database update completed successfully!");
            $this->info("🔍 Test with: curl 'localhost:8000/geoip/stats'");

            return 0;
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('GeoIP update failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Create necessary directories
     */
    private function createDirectories()
    {
        $this->info("📂 Creating directories...");

        $directories = [
            $this->config['storage_dir'] . '/maxmind',
            $this->config['storage_dir'] . '/dbip',
            $this->config['backup_dir']
        ];

        foreach ($directories as $dir) {
            $fullPath = storage_path($dir);
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
                $this->line("  ✓ Created: {$fullPath}");
            }
        }
    }

    /**
     * Update MaxMind databases
     */
    private function updateMaxMind($force = false, $noBackup = false)
    {
        $this->info("🗺️  Updating MaxMind GeoLite2 databases...");

        $licenseKey = env('MAXMIND_LICENSE_KEY');
        if (!$licenseKey) {
            $this->warn("⚠️  MaxMind license key not found in .env file");
            $this->warn("   Add MAXMIND_LICENSE_KEY to your .env file");
            $this->warn("   Get your key from: https://www.maxmind.com/en/my_license_key");
            return;
        }

        if (!$noBackup) {
            $this->backupDatabases('maxmind');
        }

        foreach ($this->config['maxmind_databases'] as $database) {
            $this->info("📥 Downloading {$database}...");

            $url = "https://download.maxmind.com/app/geoip_download?edition_id={$database}&license_key={$licenseKey}&suffix=tar.gz";
            $tempFile = sys_get_temp_dir() . "/{$database}.tar.gz";
            $tempDir = sys_get_temp_dir() . "/{$database}_extract";

            try {
                // Download
                $this->downloadFile($url, $tempFile);

                // Extract
                $this->extractTarGz($tempFile, $tempDir);

                // Find and move .mmdb file
                $mmdbFile = $this->findMmdbFile($tempDir);
                if ($mmdbFile) {
                    $destination = storage_path($this->config['storage_dir'] . "/maxmind/{$database}.mmdb");
                    copy($mmdbFile, $destination);
                    $this->line("  ✓ Updated: {$database}.mmdb");
                } else {
                    throw new Exception("No .mmdb file found in {$database} archive");
                }

                // Cleanup
                $this->cleanup([$tempFile, $tempDir]);
            } catch (Exception $e) {
                $this->error("  ❌ Failed to update {$database}: " . $e->getMessage());
                throw $e;
            }
        }

        $this->info("✅ MaxMind databases updated successfully");
    }

    /**
     * Update MaxMind databases using git.io mirrors
     */
    private function updateMaxMind2($force = false, $noBackup = false)
    {
        $this->info("🗺️  Updating MaxMind GeoLite2 databases (git.io mirrors)...");

        if (!$noBackup) {
            $this->backupDatabases('maxmind');
        }

        $databases = [
            'GeoLite2-ASN' => 'https://git.io/GeoLite2-ASN.mmdb',
            'GeoLite2-City' => 'https://git.io/GeoLite2-City.mmdb',
            'GeoLite2-Country' => 'https://git.io/GeoLite2-Country.mmdb'
        ];

        foreach ($databases as $database => $url) {
            $this->info("📥 Downloading {$database}...");

            $destination = storage_path($this->config['storage_dir'] . "/maxmind/{$database}.mmdb");

            try {
                // Download directly to destination
                $this->downloadFile($url, $destination);
                $this->line("  ✓ Updated: {$database}.mmdb");
            } catch (Exception $e) {
                $this->error("  ❌ Failed to update {$database}: " . $e->getMessage());
                throw $e;
            }
        }

        $this->info("✅ MaxMind databases updated successfully (git.io mirrors)");
    }

    /**
     * Update DB-IP databases
     */
    private function updateDBIP($force = false, $noBackup = false)
    {
        $this->info("🌐 Updating DB-IP Lite databases...");

        if (!$noBackup) {
            $this->backupDatabases('dbip');
        }

        $currentDate = date('Y-m');
        $previousDate = date('Y-m', strtotime('-1 month'));

        $databases = [
            'city' => 'dbip-city-lite.mmdb',
            'asn' => 'dbip-asn-lite.mmdb',
            'country' => 'dbip-country-lite.mmdb'
        ];

        foreach ($databases as $type => $filename) {
            $this->info("📥 Downloading DB-IP {$type}...");

            $url = str_replace('{date}', $currentDate, $this->config['dbip_urls'][$type]);
            $tempFile = sys_get_temp_dir() . "/{$filename}.gz";

            try {
                // Try current month first
                if (!$this->downloadFile($url, $tempFile, false)) {
                    // Try previous month
                    $this->warn("  ⚠️  Current month not available, trying previous month...");
                    $url = str_replace('{date}', $previousDate, $this->config['dbip_urls'][$type]);
                    $this->downloadFile($url, $tempFile);
                }

                // Decompress
                $destination = storage_path($this->config['storage_dir'] . "/dbip/{$filename}");
                $this->decompressGz($tempFile, $destination);
                $this->line("  ✓ Updated: {$filename}");

                // Cleanup
                unlink($tempFile);
            } catch (Exception $e) {
                $this->error("  ❌ Failed to update DB-IP {$type}: " . $e->getMessage());
                // Don't throw for DB-IP as it's free and might not always be available
            }
        }

        $this->info("✅ DB-IP databases updated successfully");
    }

    /**
     * Backup existing databases
     */
    private function backupDatabases($provider)
    {
        $this->info("💾 Backing up existing {$provider} databases...");

        $sourceDir = storage_path($this->config['storage_dir'] . "/{$provider}");
        $backupDir = storage_path($this->config['backup_dir'] . "/{$provider}_" . date('Ymd_His'));

        if (is_dir($sourceDir)) {
            $files = glob($sourceDir . "/*.mmdb");
            if (!empty($files)) {
                mkdir($backupDir, 0755, true);
                foreach ($files as $file) {
                    copy($file, $backupDir . "/" . basename($file));
                }
                $this->line("  ✓ Backed up to: " . basename($backupDir));
            }
        }
    }

    /**
     * Download file with curl
     */
    private function downloadFile($url, $destination, $throwOnError = true)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GeoIP-API-Updater/1.0');

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $data === false) {
            if ($throwOnError) {
                throw new Exception("Failed to download from {$url} (HTTP {$httpCode})");
            }
            return false;
        }

        file_put_contents($destination, $data);
        return true;
    }

    /**
     * Extract tar.gz file
     */
    private function extractTarGz($tarFile, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $phar = new \PharData($tarFile);
        $phar->extractTo($destination, null, true);
    }

    /**
     * Decompress .gz file
     */
    private function decompressGz($gzFile, $destination)
    {
        $gz = gzopen($gzFile, 'rb');
        $out = fopen($destination, 'wb');

        if (!$gz || !$out) {
            throw new Exception("Failed to decompress {$gzFile}");
        }

        while (!gzeof($gz)) {
            fwrite($out, gzread($gz, 4096));
        }

        gzclose($gz);
        fclose($out);
    }

    /**
     * Find .mmdb file in directory
     */
    private function findMmdbFile($directory)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Cleanup temporary files and directories
     */
    private function cleanup($paths)
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Verify database files
     */
    private function verifyDatabases()
    {
        $this->info("🔍 Verifying database files...");

        $databases = [
            'maxmind/GeoLite2-City.mmdb' => 50000000,  // At least 50MB
            'maxmind/GeoLite2-ASN.mmdb' => 9000000,    // At least 9MB
            'maxmind/GeoLite2-Country.mmdb' => 9000000,    // At least 500KB
            'dbip/dbip-city-lite.mmdb' => 100000000,     // At least 100MB
            'dbip/dbip-asn-lite.mmdb' => 9000000,        // At least 9MB
            'dbip/dbip-country-lite.mmdb' => 5000000        // At least 5MB
        ];

        foreach ($databases as $db => $minSize) {
            $path = storage_path($this->config['storage_dir'] . "/{$db}");
            if (file_exists($path)) {
                $size = filesize($path);
                if ($size >= $minSize) {
                    $this->line("  ✓ {$db} verified (" . $this->formatBytes($size) . ")");
                } else {
                    $this->warn("  ⚠️  {$db} seems too small (" . $this->formatBytes($size) . ")");
                }
            } else {
                $this->warn("  ⚠️  {$db} not found");
            }
        }
    }

    /**
     * Clean up old backups (keep last 5)
     */
    private function cleanupOldBackups()
    {
        $this->info("🧹 Cleaning up old backups...");

        $backupDir = storage_path($this->config['backup_dir']);
        if (!is_dir($backupDir)) {
            return;
        }

        foreach (['maxmind_*', 'dbip_*'] as $pattern) {
            $dirs = glob($backupDir . "/{$pattern}", GLOB_ONLYDIR);
            if (count($dirs) > 5) {
                rsort($dirs); // Sort newest first
                $toDelete = array_slice($dirs, 5); // Keep first 5, delete rest

                foreach ($toDelete as $dir) {
                    $this->removeDirectory($dir);
                    $this->line("  ✓ Removed old backup: " . basename($dir));
                }
            }
        }
    }

    /**
     * Set correct permissions
     */
    private function setPermissions()
    {
        $this->info("🔐 Setting permissions...");

        $storageDir = storage_path($this->config['storage_dir']);

        // Set directory permissions
        chmod($storageDir, 0755);

        // Set file permissions for .mmdb files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storageDir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'mmdb') {
                chmod($file->getPathname(), 0644);
            }
        }

        $this->line("  ✓ Permissions set");
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
