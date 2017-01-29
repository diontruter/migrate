<?php namespace Diontruter\Migrate;

use Exception;

/**
 * Wrapper around a migration script. Analyses the script to get the migration ID and direction.
 *
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class MigrationScript
{
    /** @var int */
    public $id;
    /** @var string */
    public $upOrDown;
    /** @var string */
    public $fileName;

    /**
     * Constructs the object from a file name.
     *
     * @param $fileName
     * @throws Exception if the file is not correctly formatted
     */
    public function __construct($fileName)
    {
        $dashPos = strpos($fileName, '-');
        if ($dashPos !== false) {
            $id = substr($fileName, 0, $dashPos);
            if (is_numeric($id)) {
                $id = intval($id);
                if (strlen($fileName) > $dashPos) {
                    $upOrDown = strtolower($fileName[$dashPos + 1]);
                    if (in_array($upOrDown, ['u', 'd'])) {
                        $this->id = $id;
                        $this->upOrDown = $upOrDown;
                        $this->fileName = $fileName;
                        return;
                    }
                }
            }
        }
        $this->badFileFormatException($fileName);
    }

    /**
     * Throw an instructive Exception.
     *
     * @param $fileName
     * @throws Exception
     */
    private function badFileFormatException($fileName)
    {
        throw new Exception(
            "$fileName does not have the required format: '<id>-<u|d>[description].sql'. Valid examples:" .
            "123-up-2017-01-01-new-year-fix, 123-down-2017-01-01-reverse-new-year-fix,  123-U, 123-d, " .
            "00123-upwards-migration, 00123-downwards-migration"
        );
    }
}