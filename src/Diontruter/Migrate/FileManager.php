<?php namespace Diontruter\Migrate;

/**
 * Encapsulates file operations performed by the application.
 *
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class FileManager
{
    /** @var string */
    private $basePath;
    /** @var string */
    private $migrationsPath;

    /**
     * FileManager constructor, specifying the configuration to use.
     *
     * @param Configuration $configuration The configuration to use
     */
    public function __construct(Configuration $configuration)
    {
        $this->basePath = $configuration->getConfigValue('basePath', './database');
        $this->migrationsPath = "$this->basePath/migration-sql";
    }

    /**
     * Create the script directories where scripts will be read. The configuration specifies a base path which
     * is first created if needed, then a migrations path is created within that path.
     */
    public function createScriptDirectories()
    {
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }

        if (!file_exists( $this->migrationsPath)) {
            mkdir($this->migrationsPath, 0777, true);
        }
    }

    /**
     * Reads migration scripts and packages them as an array of MigrationScript objects. The return array is keyed
     * by migration ID and direction of the migration. i.e. 123 => 'u' => object is the upward migration for ID 123.
     *
     * @return array
     */
    public function getMigrationFiles()
    {
        $return = [];
        $dirHandle = opendir($this->migrationsPath);
        while (false !== ($fileName = readdir($dirHandle))) {
            if (strrpos(strtolower($fileName), '.sql') !== false) {
                $migration = new MigrationScript($fileName);
                $return[$migration->id][$migration->upOrDown] = $migration;
            }
        }
        ksort($return);
        return $return;
    }

    /**
     * Gets the full path of a migration script.
     *
     * @param MigrationScript $migration The migration script
     * @return string The full path to the file of the migration script
     */
    public function getFullPath(MigrationScript $migration)
    {
        return "$this->migrationsPath/{$migration->fileName}";
    }
}
