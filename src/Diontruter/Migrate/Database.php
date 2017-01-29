<?php namespace Diontruter\Migrate;

use Exception;
use PDO;
use PDOStatement;

/**
 * Simple wrapper around PDO to help with a consistent and compact approach.
 * 
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class Database
{
    /** @var PDO */
    private $pdo;

    /**
     * Database constructor specifying connection settings. Internally sets error mode to ERRMODE_EXCEPTION.
     *
     * @param string $connectionString
     * @param string $userName
     * @param string $password
     */
    public function __construct($connectionString, $userName, $password)
    {
        $this->pdo = new PDO($connectionString, $userName, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table Table to search for
     * @return bool true if the table exists, false the table was not found
     */
    function tableExists($table) {

        // Try a select statement against the table
        // Run it in try/catch as PDO is in ERRMODE_EXCEPTION.
        try {
            $result = $this->pdo->query("SELECT 1 FROM $table LIMIT 1");
        } catch (Exception $e) {
            // We got an exception == table not found
            return false;
        }

        // Result is either boolean false (no table found) or PDOStatement Object (table found)
        return $result !== false;
    }

    /**
     * Execute an update statement.
     *
     * @param string $sql The SQL to execute
     * @param null|array $parameters Optional associative array of parameter names and values
     * @return bool The value returned when executing the SQL
     */
    function execute($sql, $parameters = null)
    {
        $statement = $this->prepare($sql, $parameters);
        $success = $statement->execute();
        return $success;
    }

    /**
     * Perform a SQL query that will return rows and columns.
     *
     * @param string $sql The SQL query
     * @param string $fetchClass The class name containing fields that match the return columns
     * @param null|array $parameters Optional associative array of parameter names and values
     * @return Object[] The result set as an array of objects
     */
    function query($sql, $fetchClass, $parameters = null)
    {
        $statement = $this->prepare($sql, $parameters);
        $statement->execute();
        $return = $statement->fetchAll(PDO::FETCH_CLASS, $fetchClass);
        return $return;
    }

    /**
     * Perform a SQL query to get a single value.
     *
     * @param string $sql The SQL query
     * @param int $column The column of the resultset that contains the desired value
     * @param null|array $parameters Optional associative array of parameter names and values
     * @return mixed The value of the requested column in the SQL results
     */
    function queryColumn($sql, $column, $parameters = null)
    {
        $statement = $this->prepare($sql, $parameters);
        $statement->execute();
        $return = $statement->fetchColumn($column);
        return $return;
    }

    /**
     * Prepare a SQL statement.
     *
     * @param string $sql The SQL statement
     * @param null|array $parameters Optional associative array of parameter names and values
     * @return PDOStatement The prepared statement
     */
    private function prepare($sql, $parameters)
    {
        // prepare sql and bind parameters
        $statement = $this->pdo->prepare($sql);
        if ($parameters) {
            foreach ($parameters as $name => $value) {
                if (strlen($name) > 0 && $name[0] != ':') {
                    $name = ":$name";
                }
                $statement->bindParam($name, $value);
            }
        }
        return $statement;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Initiates a transaction
     *
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Commits a transaction
     *
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Rolls back a transaction
     *
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

}
