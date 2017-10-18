<?php
/**
 * Copyright (c) 2017 Josh P (joshp.xyz).
 */

namespace J0sh0nat0r\SimpleCache\Drivers;

use J0sh0nat0r\SimpleCache\Exceptions\DriverInitializationFailedException;
use J0sh0nat0r\SimpleCache\Exceptions\DriverOptionsInvalidException;
use J0sh0nat0r\SimpleCache\IDriver;
use SQLite3;

/**
 * SQLite driver.
 *
 * Accepted options:
 *   * file           (required) File to store the database in.
 *   * encryption_key (optional) If specified, will be used to encrypt data.
 */
class SQLite implements IDriver
{
    private $db;
    private $table_name = 'cache';

    /**
     * SQLite constructor.
     *
     * @param $options
     *
     * @throws DriverOptionsInvalidException
     * @throws DriverInitializationFailedException
     */
    public function __construct($options)
    {
        if (!isset($options['file'])) {
            throw new DriverOptionsInvalidException('The `file` option must be set');
        }

        if (!is_file($options['file'])) {
            if (!touch($options['file'])) {
                throw new DriverInitializationFailedException(
                    'The database file was not found and could not be automatically created'
                );
            }
        }

        if (isset($options['table_name'])) {
            if (!is_string($options['table_name'])) {
                throw new DriverOptionsInvalidException('The `table_name` option must be a `string`');
            }

            $this->table_name = $options['table_name'];
        }

        $encryption_key = null;

        if (isset($options['encryption_key'])) {
            if (!is_string($options['encryption_key'])) {
                throw new DriverOptionsInvalidException('The `encryption_key` option must be a string');
            }

            $encryption_key = $options['encryption_key'];
        }

        $this->db = new SQLite3($options['file'], null, $encryption_key);

        if (!$this->db->query(
            "CREATE TABLE IF NOT EXISTS \"$this->table_name\" (k TEXT PRIMARY KEY, v TEXT, e INT)"
        )) {
            throw new DriverOptionsInvalidException('Failed to create database table');
        }

        if (!$this->clearExpiredItems()) {
            throw new DriverInitializationFailedException('Failed to clear expired items');
        }
    }

    /**
     * Clears expired items from the cache.
     *
     * @return bool
     */
    private function clearExpiredItems()
    {
        return (bool) $this->db->query(
            "DELETE FROM \"$this->table_name\" WHERE e <= strftime('%s','now') AND e > 0"
        );
    }

    public function put($key, $value, $time)
    {
        $stmt = $this->db->prepare("INSERT INTO \"$this->table_name\" VALUES (:k, :v, :e)");

        $stmt->bindParam('k', $key, SQLITE3_TEXT);
        $stmt->bindParam('v', $value, SQLITE3_TEXT);
        $stmt->bindParam('e', $time, SQLITE3_INTEGER);

        return (bool) $stmt->execute();
    }

    public function remove($key)
    {
        if (!$this->has($key)) {
            return true;
        }

        $stmt = $this->db->prepare("DELETE FROM \"$this->table_name\" WHERE k = :k");

        $stmt->bindParam('k', $key, SQLITE3_TEXT);

        return (bool) $stmt->execute();
    }

    public function has($key)
    {
        return $this->get($key) !== null;
    }

    public function get($key)
    {
        $stmt = $this->db->prepare("SELECT * FROM \"$this->table_name\" WHERE k = :k");

        $stmt->bindParam('k', $key, SQLITE3_TEXT);

        $results = $stmt->execute()->fetchArray();

        if (count($results) < 1) {
            return null;
        }

        return $results[0]['v'];
    }

    public function clear()
    {
        return (bool) $this->db->query("DELETE FROM \"$this->table_name\"");
    }
}
