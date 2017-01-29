<?php namespace Diontruter\Migrate;

/**
 * Object that is used to package the results of querying the migrations table.
 *
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class MigrationDto
{
    /** @var int */
    public $id;
    /** @var string */
    public $file_name;
}
