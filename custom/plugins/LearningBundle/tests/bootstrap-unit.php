<?php declare(strict_types=1);

/**
 * Bootstrap file for UNIT TESTS ONLY
 * 
 * This file only loads the Composer autoloader.
 * It does NOT connect to database or bootstrap Shopware.
 * 
 * Unit tests should be fast and isolated - they don't need database.
 */

// Load Composer autoloader (4 levels up: tests → LearningBundle → plugins → custom → root)
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Register plugin's PSR-4 namespace for autoloading
$loader = require __DIR__ . '/../../../../vendor/autoload.php';
$loader->addPsr4('Learning\\Bundle\\', __DIR__ . '/../src/');
