<?php

/**
 * Test bootstrap for Lab Order Queue module.
 * Loads the OpenEMR vendor autoloader and registers the module namespace.
 */

// OpenEMR vendor autoloader (provides PHPUnit + OpenEMR classes)
$vendorAutoload = dirname(__DIR__, 5) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Register the module namespace for tests
spl_autoload_register(function (string $class): void {
    $prefix = 'Juggernaut\\OpenEMR\\Modules\\LabOrderQueue\\';
    $baseDir = dirname(__DIR__) . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Register test namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'Juggernaut\\OpenEMR\\Modules\\LabOrderQueue\\Tests\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
