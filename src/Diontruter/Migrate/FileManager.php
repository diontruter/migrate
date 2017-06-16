<?php namespace Diontruter\Migrate;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
        if (!file_exists( $this->migrationsPath)) {
            $defaultDir = __DIR__ . '/../../../resources/defaults';
            $this->copyr($defaultDir, $this->migrationsPath);
            $this->filename_replace('${date}', date('Y-m-d'), $this->migrationsPath );
            echo "Sample migrations created in '$this->migrationsPath'\n";
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

    public static function copyr($source, $dest)
    {
        if (is_dir($source)) {
            mkdir($dest);
            $iterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
            $iteratorIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iteratorIterator as $item) {
                if ($item->isDir()) {
                    self::mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                } else {
                    self:copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                }
            }
        } else {
            self::copy($source, $dest);
        }
    }

    /**
     * @param string $path
     * @return SplFileInfo[]
     */
    private static function getRecursiveIteratorIterator($path)
    {
        $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        /** @var SplFileInfo[] $iteratorIterator */
        $iteratorIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        return $iteratorIterator;
    }

    public static function filename_replace($search, $replace, $source)
    {
        if (is_dir($source)) {
            foreach (self::getRecursiveIteratorIterator($source) as $item) {
                self::rename($item->getPathname(), str_replace($search, $replace, $item->getPathname()));
            }
        } else {
            self::rename($source, str_replace($search, $replace, $source));
        }
    }

    public static function mkdir($pathname)
    {
        if (!mkdir($pathname)) {
            $errorMessage = error_get_last();
            throw new Exception("Cannot create '$pathname' ($errorMessage)");
        }
    }

    public static function copy($source, $dest)
    {
        if (!copy($source, $dest)) {
            $errorMessage = error_get_last();
            throw new Exception("Cannot copy '$source' to '$dest' ($errorMessage)");
        }
    }

    public static function rename($oldname, $newname)
    {
        if (!rename($oldname, $newname)) {
            $errorMessage = error_get_last();
            throw new Exception("Cannot rename '$oldname' to '$newname' ($errorMessage)");
        }
    }

}
