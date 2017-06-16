<?php namespace Diontruter\Migrate;

use Exception;

/**
 * Main class containing migration functions. Can be called in command ine mode via:
 * - SimpleMigration::processCommandLine($argv)
 * or otherwise individual functions can be used:
 * - SimpleMigration::migrate($up = true)
 * - SimpleMigration::status()
 *
 * Upward migrations will process all migration files not yet processed, and downward migrations will only run the
 * downward migration script for the latest migration.
 *
 * Migration scripts must be in this format: '<id>-<u|d>[description].sql' Valid examples:
 * - 123-up-2017-01-01-new-year-fix.sql
 * - 123-down-2017-01-01-reverse-new-year-fix.sql
 * - 123-U.sql
 * - 123-d.sql
 * - 00123-upwards-migration.sql
 * - 00123-downwards-migration.sql
 *
 * Migrations are tracked via the ID part of the file name, and grouped into upward and downward migration pairs.
 * Downward migration files are optional; when there is no downward migration for the latest upward migration an
 * error will occur when a downward migration is attempted.
 *
 * All files in the migration-scripts directory are read, and files that do not have a .sql extension are ignored.
 * Migrations are sorted by their ID prefix, and run consecutively based on their IDs.
 *
 * Migration scripts can contain arbitrary SQL statements separated by semicolons. The SqlScriptParser class
 * separates SQL statements in order to send them via PDO. It can handle the precedence between semicolons, single
 * line comments, multi line comments and quoted strings.  All SQL statements within a given script are run in a
 * single database transaction in order to guarantee integrity.
 *
 * This application uses very basic SQL that has been tested on MySQL and PostgreSQL. It should work with any SQL
 * based database, but this has not been verified yet.
 *
 * Once a migration script has been run it can be safely renamed, as long as the parsed ID part equals the original
 * integer ID value when it was first run. If an upward migration script is deleted or the ID part is changed, it will
 * have no effect as only the down migration will be needed in future. After downgrading past the deleted upward
 * migration, future upward migrations will exclude the deleted file. Always add new migration scripts with a higher
 * ID than the ID of the last script. The migration tool will only process new migrations if their ID is larger
 * than the last migration ID that was run.
 *
 * This class takes a configuration path when constructed. The configuration path must be a plain PHP file that
 * returns a configuration array. The array must have this structure:
 *
 * <?php
 * return [
 *     'basePath' => 'path-within-which-to-find-migration-scripts-directory',
 *     'connectionString' => 'pdo-database-connection-string',
 *     'userName' => 'optional-pdo-username',
 *     'password' => 'optional-pdo-password'
 * ];
 *
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class SimpleMigration
{
    /** @var Configuration */
    private $configuration;
    /** @var MigrationRepository */
    private $migrationRepository;
    /** @var FileManager */
    private $fileManager;

    /**
     * SimpleMigration constructor, requires a configuration path.
     *
     * @param string|null $configPath
     * @throws Exception If the specified configuration path ($configPath) does not exist or is not a directory
     */
    public function __construct($configPath = null)
    {
        if (!$configPath) {
            $configPath = $this->resolveConfigPath();
        }
        $this->checkConfigPath($configPath);
        $this->configuration = new Configuration($configPath);
    }

    private function resolveConfigPath()
    {
        $directories = array(__DIR__ . '/../../../config', __DIR__ . '/../../config', __DIR__ . '/../config', __DIR__ . '/config');

        foreach ($directories as $directory) {
            if (file_exists($directory)) {
                return "$directory/migration.php";
            }
        }

        $checkedDirs = implode(', ', $directories);
        throw new Exception("Config directory not in one of $checkedDirs.\n");
    }

    private function checkConfigPath($configPath)
    {
        if (!file_exists($configPath)) {
            $defaultDir = __DIR__ . '/../../../resources/defaults/migration.php';
            FileManager::copy($defaultDir, $configPath);
            throw new Exception("Configuration file '$configPath' not found, created a sample");
        }

        if (!file_exists($configPath) || !is_file($configPath)) {
            throw new Exception("Configuration file '$configPath' does not exist or is not a directory");
        }
    }

    /**
     * Process migration commands sent via the command line.
     *
     * @param array $argv The PHP command line parameters
     * @throws Exception if the arguments are incorrect
     */
    public function processCommandLine($argv)
    {
        if (isset($argv[1])) {
            $command = $argv[1];
        } else {
            $command = 'up';
        }

        try {
            switch ($command) {
                case 'up':
                    $this->migrate(true);
                    break;
                case 'down':
                    $this->migrate(false);
                    break;
                case 'status':
                    $this->status();
                    break;
                default:
                    throw new Exception(
                        "Unrecognized command: '$command'. Valid commands are up (default), down and status.");
            }
        } catch (Exception $e) {
            echo "{$e->getMessage()}\n";
        }
    }

    /**
     * Run an upward or downward migration.
     *
     * @param bool $up True for up, false for down
     */
    public function migrate($up = true)
    {
        $this->prepare();
        $migrations = $this->fileManager->getMigrationFiles();
        if ($up) {
            $this->migrateUp($migrations);
        } else {
            $this->migrateDown($migrations);
        }
    }

    /**
     * Print out all migrations that have been applied.
     */
    public function status()
    {
        $this->prepare();
        $migrations = $this->migrationRepository->getMigrationsDone();
        if (empty($migrations)) {
            echo "No migrations to show\n";
        } else {
            foreach ($migrations as $migrationDto) {
                $id = str_pad($migrationDto->id, 5, '0', STR_PAD_LEFT);
                echo "$id : $migrationDto->file_name\n";
            }
        }
    }

    /**
     * SimpleMigration::migrate($up = true) and SimpleMigration::status() both call this internal function
     * to set up the database and file system for processing.
     */
    private function prepare()
    {
        $this->migrationRepository = new MigrationRepository($this->configuration);
        $this->migrationRepository->createMigrationTable();

        $this->fileManager = new FileManager($this->configuration);
        $this->fileManager->createScriptDirectories();
    }

    /**
     * Perform a downward migration by running the down script of the last migration.
     *
     * @param array $migrations All migrations.
     * @throws Exception
     */
    private function migrateDown(array $migrations)
    {
        $lastId = $this->migrationRepository->getLastId();
        if ($lastId !== false) {
            if (isset($migrations[$lastId])) {
                $upAndDownMigrations = $migrations[$lastId];
                if (isset($upAndDownMigrations['d'])) {
                    $migration = $upAndDownMigrations['d'];
                    $this->runMigration($migration, 'd');
                } else {
                    throw new Exception("Last migration [$lastId] has no downward migration script");
                }
            } else {
                throw new Exception("Script files for last migration [$lastId] are missing");
            }
        }
    }

    /**
     * Run all u;ward migrations with an ID higher than the last upward migration that was run.
     *
     * @param array $migrations All migrations
     */
    private function migrateUp(array $migrations)
    {
        foreach ($migrations as $id => $upAndDownMigrations) {
            if (isset($upAndDownMigrations['u'])) {
                $migration = $upAndDownMigrations['u'];
                if (!$this->migrationRepository->isMigrationDone($migration)) {
                    $this->runMigration($migration, 'u');
                }
            }
        }
    }

    /**
     * Run one migration.
     *
     * @param MigrationScript $migration The migration to run
     * @param string $upOrDown Treat the migration as an upward ('u') or downward ('d') migration
     * @throws Exception
     */
    private function runMigration(MigrationScript $migration, $upOrDown)
    {
        $parser = new SqlScriptParser();
        $sqlStatements = $parser->parse($this->fileManager->getFullPath($migration));

        $this->migrationRepository->database->beginTransaction();
        try {
            $statements = 0;
            foreach ($sqlStatements as $statement) {
                $distilled = $parser->removeComments($statement);
                if (!empty($distilled)) {
                    $statements += $this->migrationRepository->database->execute($distilled);
                }
            }
            if ($upOrDown == 'u') {
                $this->migrationRepository->markMigrationDone($migration);
            } else {
                $this->migrationRepository->deleteMigration($migration);
            }
            echo "$migration->fileName - $statements statement(s) executed\n";
            $this->migrationRepository->database->commit();
        } catch (Exception $e) {
            $this->migrationRepository->database->rollBack();
            throw new Exception("Error processing $migration->fileName: {$e->getMessage()}", 0, $e);
        }
    }

}
