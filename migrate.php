#!/usr/bin/env php
<?php

/**
 * Command line entry point for SimpleMigration. Specifies the configuration file location and calls
 * SimpleMigration via its command line interface.
 *
 * @author Dion Truter <dion@truter.org>
 */

use Diontruter\Migrate\SimpleMigration;

$files = array(__DIR__ . '/../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php');
$loader = null;

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;
        break;
    }
}

if (!$loader) {
    echo "vendor/autoload.php could not be found. Did you run `php composer.phar install`?\n";
}

$migration = new SimpleMigration();
$migration->processCommandLine($argv);
