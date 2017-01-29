<?php
/**
 * Command line entry point for SimpleMigration. Specifies the configuration file location and calls
 * SimpleMigration via its command line interface.
 *
 * @author Dion Truter <dion@truter.org>
 */

use Diontruter\Migrate\SimpleMigration;

require __DIR__ . '/vendor/autoload.php';
$migration = new SimpleMigration('config/migration.php');
$migration->processCommandLine($argv);
