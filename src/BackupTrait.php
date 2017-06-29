<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace DatabaseBackup;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use InvalidArgumentException;
use ReflectionClass;

/**
 * A trait that provides some methods used by all other classes
 */
trait BackupTrait
{
    /**
     * Returns an absolute path
     * @param string $path Relative or absolute path
     * @return string
     * @uses getTarget()
     */
    public function getAbsolutePath($path)
    {
        if (!Folder::isAbsolute($path)) {
            return $this->getTarget() . DS . $path;
        }

        return $path;
    }

    /**
     * Gets a binary path
     * @param string $name Binary name
     * @return string
     * @since 2.0.0
     * @throws InvalidArgumentException
     */
    public function getBinary($name)
    {
        $binary = Configure::read(DATABASE_BACKUP . '.binaries.' . $name);

        if (!$binary) {
            throw new InvalidArgumentException(__d('database_backup', '`{0}` executable not available', $name));
        }

        return $binary;
    }

    /**
     * Returns the compression type from a filename
     * @param string $filename Filename
     * @return string|bool Compression type as string or `false`
     * @uses getExtension()
     */
    public function getCompression($filename)
    {
        //Gets the extension from the filename
        $extension = $this->getExtension($filename);

        if (!array_key_exists($extension, VALID_COMPRESSIONS)) {
            return false;
        }

        return VALID_COMPRESSIONS[$extension];
    }

    /**
     * Gets the short name for class namespace
     * @param string $class Class namespace
     * @return string
     */
    public function getClassShortName($class)
    {
        return (new ReflectionClass($class))->getShortName();
    }

    /**
     * Gets the connection array
     * @param string|null $name Connection name
     * @return \Cake\Datasource\ConnectionInterface A connection object
     */
    public function getConnection($name = null)
    {
        if (!$name) {
            $name = Configure::read(DATABASE_BACKUP . '.connection');
        }

        return ConnectionManager::get($name);
    }

    /**
     * Gets the driver containing all methods to export/import database backups
     *  according to the database engine
     * @param \Cake\Datasource\ConnectionInterface|null $connection A connection object
     * @return object A driver instance
     * @since 2.0.0
     * @throws InvalidArgumentException
     * @uses getConnection()
     * @uses getClassShortName()
     */
    public function getDriver(ConnectionInterface $connection = null)
    {
        if (!$connection) {
            $connection = $this->getConnection();
        }

        $className = $this->getClassShortName($connection->getDriver());
        $driver = App::classname(DATABASE_BACKUP . '.' . $className, 'Driver');

        if (!$driver) {
            throw new InvalidArgumentException(__d('database_backup', 'The `{0}` driver does not exist', $className));
        }

        return new $driver($connection);
    }

    /**
     * Returns the extension of a filename
     * @param string $filename Filename
     * @return string|null Extension or `null` on failure
     */
    public function getExtension($filename)
    {
        $regex = sprintf('/\.(%s)$/', implode('|', array_map('preg_quote', VALID_EXTENSIONS)));

        if (preg_match($regex, $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Gets all tables of the database
     * @param \Cake\Datasource\ConnectionInterface|null $connection A connection object
     * @return array
     */
    public function getTables(ConnectionInterface $connection = null)
    {
        if (!$connection) {
            $connection = $this->getConnection();
        }

        return $connection->getSchemaCollection()->listTables();
    }

    /**
     * Returns the target path
     * @return string
     */
    public function getTarget()
    {
        return Configure::read(DATABASE_BACKUP . '.target');
    }

    /**
     * Truncates tables.
     *
     * Some drivers (eg. Sqlite) are not able to truncates tables before import
     *  a backup file. For this reason, it may be necessary to run it manually.
     * @return void
     * @uses getTables()
     */
    public function truncateTables(ConnectionInterface $connection = null)
    {
        if (!$connection) {
            $connection = $this->getConnection();
        }

        foreach ($this->getTables($connection) as $table) {
            $connection->delete($table);
        }
    }
}
