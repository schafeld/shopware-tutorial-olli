<?php declare(strict_types=1);

// Bootstrap file for PHPUnit tests
// This file loads the Shopware test environment

use Shopware\Core\TestBootstrapper;

$loader = require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../vendor/shopware/core/TestBootstrapper.php';

return (new TestBootstrapper())
    ->addCallingPlugin()
    ->bootstrap()
    ->getClassLoader();
