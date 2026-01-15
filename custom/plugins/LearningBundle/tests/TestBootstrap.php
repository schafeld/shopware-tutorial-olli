<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('LearningBundle')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('Learning\\Bundle\\Tests\\', __DIR__);

// Explicitly register the Integration test trait
require_once __DIR__ . '/Integration/IntegrationTestBehaviour.php';
