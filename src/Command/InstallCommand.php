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
        
        $this->downloadWordPress($version, $targetDir);
        $this->cleanup();
        
        echo "\n✅ Success! WordPress {$version} installed at:\n{$targetDir}\n";
    }

    private function downloadWordPress(string $version, string $targetDir): void
    {
        $tempZip = $this->createTempFile();
        $downloadUrl = "https://wordpress.org/wordpress-{$version}.zip";

        echo "⬇️  Downloading WordPress {$version}...\n";
        $this->downloadFile($downloadUrl, $tempZip);
        
        echo "📦 Extracting files...\n";
        $this->extractZip($tempZip, $targetDir);
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
        
        if (!$zip->extractTo($targetDir)) {
            throw new \RuntimeException('Failed to extract ZIP file');
        }
        
        $zip->close();
    }

    private function cleanup(): void
    {
        // Add any temporary file cleanup here if needed
    }

    private function showError(string $message): void
    {
        echo "\n❌ Error: {$message}\n";
    }
}