<?php

namespace WordpressCli\Command;

class InstallCommand
{
    private const WORDPRESS_API = 'https://api.wordpress.org/core/version-check/1.7/';
    
    public function execute(): void
    {
        try {
            $this->showHeader();
            $version = $this->getLatestVersion();
            $this->installWordPress($version);
        } catch (\Exception $e) {
            $this->showError($e->getMessage());
            exit(1);
        }
    }

    private function showHeader(): void
    {
        echo "================================\n";
        echo " WordPress Installation Tool\n";
        echo "================================\n\n";
    }

    private function getLatestVersion(): string
    {
        echo "🔍 Fetching latest WordPress version...\n";
        $response = file_get_contents(self::WORDPRESS_API);
        
        if (!$response) {
            throw new \RuntimeException('Failed to fetch WordPress version');
        }

        $data = json_decode($response, true);
        return $data['offers'][0]['version'];
    }

    private function installWordPress(string $version): void
    {
        $timestamp = date('YmdHis');
        $targetDir = getcwd() . "/wordpress_{$version}_{$timestamp}";
        
        $tempZip = $this->createTempFile();
        $downloadUrl = "https://wordpress.org/wordpress-{$version}.zip";

        echo "⬇️  Downloading WordPress {$version}...\n";
        $this->downloadFile($downloadUrl, $tempZip);

        echo "📦 Extracting files...\n";
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

        // Create target directory if it doesn't exist
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            throw new \RuntimeException("Failed to create target directory: {$targetDir}");
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Remove "wordpress/" from path
            $newPath = str_replace('wordpress/', '', $filename);
            
            // Skip the root directory entry
            if ($newPath === '') continue;

            $fullPath = $targetDir . '/' . $newPath;

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

    private function showError(string $message): void
    {
        echo "\n❌ Error: {$message}\n";
    }
}
