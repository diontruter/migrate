<?php namespace Diontruter\Migrate;

use Exception;

/**
 * Database operations used by the application.
 *
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class MigrationRepository
{
    /** @var Database */
    public $database;

    /**
     * MigrationRepository constructor, specifying the configuration to use.
     *
     * @param Configuration $configuration The configuration to use
     * @throws Exception if the configuration is not valid
     */
    public function __construct(Configuration $configuration)
    {
        $connectionString = $configuration->getConfigValue('connectionString');
        $userName = $configuration->getConfigValue('userName');
        $password = $configuration->getConfigValue('password');

        if (!$connectionString) {
            throw new Exception('Configuration does not contain a database connection string');
        }
        $this->database = new Database($connectionString, $userName, $password);
    }

    /**
     * Creates the migrations table if needed.
     *
     * @return bool|int
     */
    public function createMigrationTable()
    {
        $affectedRows = 0;
        if (!$this->database->tableExists('smt_migrations')) {
            $createTableScript = "
                CREATE TABLE smt_migrations (
                  id INT NOT NULL PRIMARY KEY,
                  file_name varchar(255) NOT NULL
                );
            ";
            $affectedRows = $this->database->execute($createTableScript);
        }
        return $affectedRows;
    }

    /**
     * Returns the highest migration ID, which belongs to the latest migration that was run.
     *
     * @return bool|int
     */
    public function getLastId()
    {
        $found = $this->database->queryColumn('SELECT max(id) FROM smt_migrations', 0);
        if ($found === null) {
            return false;
        }
        return $found;
    }

    /**
     * Fetches all the past migrations from the database and returns them as an array.
     *
     * @return MigrationDto[]
     */
    public function getMigrationsDone() {
        /** @var MigrationDto[] $found */
        $found = $this->database->query(
            "SELECT id, file_name FROM smt_migrations ORDER BY id", MigrationDto::class
        );
        return $found;
    }

    /**
     * Returns whether the supplied migration has already been performed.
     *
     * @param MigrationScript $migration
     * @return bool True if the migration was performed, and false otherwise
     */
    public function isMigrationDone(MigrationScript $migration) {
        $found = $this->database->queryColumn(
            "SELECT 1 FROM smt_migrations where id = :id LIMIT 1", 0,
            ['id' => $migration->id]
        );
        return ($found !== false);
    }

    /**
     * Creates a database record for the supplied migration.
     *
     * @param MigrationScript $migration
     * @return bool Result of executing the insert statement
     */
    public function markMigrationDone(MigrationScript $migration)
    {
        $success = $this->database->execute(
            "INSERT INTO smt_migrations(id, file_name) VALUES (:nextId, '{$migration->fileName}')",
            ['nextId' => $migration->id, ]
        );
        return $success;
    }

    /**
     * Deletes the database record for the supplied migration.
     *
     * @param MigrationScript $migration
     * @return bool Result of executing the delete statement
     */
    public function deleteMigration(MigrationScript $migration)
    {
        $success = $this->database->execute(
            "DELETE FROM smt_migrations WHERE id = :id",
            ['id' => $migration->id, ]
        );
        return $success;
    }

}
