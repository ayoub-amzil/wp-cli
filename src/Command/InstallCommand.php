<?php

namespace WordpressCli\Command;

class InstallCommand
{
    private const WORDPRESS_API = 'https://api.wordpress.org/core/version-check/1.7/';
    private const BASE_DOWNLOAD_URL = 'https://wordpress.org/wordpress-%s.zip';
    
    public function execute(array $args): void
    {
        try {
            $this->showHeader();
            
            $parsedArgs = $this->parseArguments($args);
            $folderName = $parsedArgs['folder'] ?? null;
            $version = $parsedArgs['version'] ?? $this->getLatestVersion();
            
            // Validate folder name if provided
            if ($folderName && !$this->validateFolderName($folderName)) {
                throw new \InvalidArgumentException(
                    "Invalid folder name. Only alphanumeric, hyphens, and underscores are allowed."
                );
            }

            // Validate version format
            if (!$this->validateVersion($version)) {
                throw new \InvalidArgumentException("Invalid version format. Use major, major.minor, or major.minor.path");
            }

            // Construct the correct download URL based on version format
            $downloadVersion = $this->getDownloadVersion($version);
            
            // Check if version exists
            if (!$this->versionExists($downloadVersion)) {
                throw new \RuntimeException("WordPress version {$downloadVersion} does not exist.");
            }

            $this->installWordPress($downloadVersion, $folderName);
        } catch (\Exception $e) {
            $this->showError($e->getMessage());
            exit(1);
        }
    }

    private function parseArguments(array $args): array
    {
        $result = ['folder' => null, 'version' => null];
        
        foreach ($args as $arg) {
            if (str_starts_with($arg, 'v=')) {
                $result['version'] = substr($arg, 2);
            } else {
                $result['folder'] = $arg;
            }
        }
        
        return $result;
    }

    private function validateFolderName(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $name);
    }

    private function validateVersion(string $version): bool
    {
        return (bool) preg_match('/^\d+(\.\d+){0,2}$/', $version);
    }

    private function getDownloadVersion(string $version): string
    {
        $parts = explode('.', $version);
        
        // Major only (e.g., v=6 → 6.0)
        if (count($parts) === 1) {
            return "{$parts[0]}.0";
        }
        
        // Major and minor (e.g., v=6.2 → 6.2)
        if (count($parts) === 2) {
            return "{$parts[0]}.{$parts[1]}";
        }
        
        // Full version (e.g., v=6.2.1 → 6.2.1)
        return $version;
    }

    private function versionExists(string $version): bool
    {
        $url = sprintf(self::BASE_DOWNLOAD_URL, $version);
        $headers = @get_headers($url);
        return $headers && str_contains($headers[0], '200');
    }

    private function getLatestVersion(): string
    {
        echo "Fetching latest WordPress version...\n";
        $response = file_get_contents(self::WORDPRESS_API);
        
        if (!$response) {
            throw new \RuntimeException('Failed to fetch WordPress version');
        }

        $data = json_decode($response, true);
        return $data['offers'][0]['version'];
    }

    private function installWordPress(string $version, ?string $customName = null): void
    {
        $t = time();
        // Use custom name if provided and valid, otherwise use version-based name
        $folderName = $customName ?? "wordpress_{$version}_{$t}";
        $targetDir = getcwd() . DIRECTORY_SEPARATOR . $folderName;
        
        // Check if directory already exists
        if (is_dir($targetDir)) {
            throw new \RuntimeException("Directory already exists: {$folderName}");
        }

        $tempZip = $this->createTempFile();
        $downloadUrl = sprintf(self::BASE_DOWNLOAD_URL, $version);

        echo "⬇️  Downloading WordPress {$version}...\n";
        $this->downloadFile($downloadUrl, $tempZip);

        echo "Extracting files to: {$folderName}\n";
        $this->extractZip($tempZip, $targetDir);

        $this->cleanupTempFile($tempZip);

        echo "\n✅ Success! WordPress {$version} installed at:\n{$targetDir}\n";
    }

    private function createTempFile(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wp_');
        if (!$tempFile) {
            throw new \RuntimeException('Failed to create temporary file');
        }
        return $tempFile . '.zip';
    }

    private function downloadFile(string $url, string $destination): void
    {
        $content = file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException("Download failed: {$url}");
        }
        
        if (file_put_contents($destination, $content) === false) {
            throw new \RuntimeException("Failed to save file: {$destination}");
        }
    }

    private function extractZip(string $zipPath, string $targetDir): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open ZIP archive');
        }

        // Create target directory
        if (!mkdir($targetDir, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: {$targetDir}");
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Remove "wordpress/" from path
            $newPath = str_replace('wordpress/', '', $filename);
            
            // Skip the root directory entry
            if ($newPath === '') continue;

            $fullPath = $targetDir . DIRECTORY_SEPARATOR . $newPath;

            // Handle directory entries
            if (substr($filename, -1) === '/') {
                if (!is_dir($fullPath)) {
                    if (!mkdir($fullPath, 0755, true)) {
                        throw new \RuntimeException("Failed to create directory: {$fullPath}");
                    }
                }
            } else {
                // Ensure parent directory exists
                $parentDir = dirname($fullPath);
                if (!is_dir($parentDir)) {
                    if (!mkdir($parentDir, 0755, true)) {
                        throw new \RuntimeException("Failed to create parent directory: {$parentDir}");
                    }
                }

                // Extract file
                $contents = $zip->getFromIndex($i);
                if (file_put_contents($fullPath, $contents) === false) {
                    throw new \RuntimeException("Failed to write file: {$fullPath}");
                }
            }
        }

        $zip->close();
    }

    private function cleanupTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    private function showHeader(): void
    {
        echo "================================\n";
        echo " WordPress Installation Tool\n";
        echo "================================\n\n";
    }

    private function showError(string $message): void
    {
        echo "\n❌ Error: {$message}\n";
    }
}
