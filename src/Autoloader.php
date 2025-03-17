<?php

namespace WordpressCli;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(function ($className) {
            // Convert namespace to file path
            $baseDir = __DIR__ . '/../';
            $file = $baseDir . str_replace(
                ['\\', 'WordpressCli/'],
                ['/', 'src/'], 
                $className
            ) . '.php';

            $file = str_replace('//', '/', $file);
            
            if (file_exists($file)) {
                require $file;
                return true;
            }
            
            return false;
        });
    }
}