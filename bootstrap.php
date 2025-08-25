<?php
declare(strict_types=1);

// Simple PSR-4 autoloader for the App\\ namespace without Composer
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return; // not our namespace
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

// Environment checks
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This worker must be run from the CLI.\n");
    exit(1);
}

if (!function_exists('pcntl_fork')) {
    fwrite(STDERR, "The pcntl extension is required. Enable it in your PHP CLI.\n");
    exit(1);
}

if (!function_exists('posix_kill')) {
    fwrite(STDERR, "The posix extension is required. Enable it in your PHP CLI.\n");
    exit(1);
}

