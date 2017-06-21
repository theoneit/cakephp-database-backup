<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @since       2.0.0
 */
namespace MysqlBackup\Driver;

use Cake\Network\Exception\InternalErrorException;
use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Driver;

/**
 * Mysql driver to export/import database backups
 */
class Mysql extends Driver
{
    use BackupTrait;

    /**
     * Default extension for export
     * @var string
     */
    public $defaultExtension = 'sql';

    /**
     * Gets the executable command to export the database
     * @param string $filename Filename where you want to export the database
     * @return string
     * @uses $connection
     * @uses getCompression()
     * @uses getValidCompressions()
     */
    protected function getExportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $mysqldumpBinary = $this->getBinary('mysqldump');

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            return sprintf('%s --defaults-file=%%s %s | %s > %s 2>/dev/null', $mysqldumpBinary, $this->connection['database'], $this->getBinary($compression), $filename);
        }

        //No compression
        return sprintf('%s --defaults-file=%%s %s > %s 2>/dev/null', $mysqldumpBinary, $this->connection['database'], $filename);
    }

    /**
     * Stores the authentication data, to be used to export the database, in a
     *  temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @return string Path of the temporary file
     * @uses $connection
     */
    private function getExportStoreAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->connection['username'],
            empty($this->connection['password']) ? null : $this->connection['password'],
            $this->connection['host']
        ));

        return $auth;
    }

    /**
     * Gets the executable command to import the database
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @uses $connection
     * @uses getCompression()
     * @uses getValidCompressions()
     */
    protected function getImportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $mysqlBinary = $this->getBinary('mysql');

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            return sprintf('%s -dc %s | %s --defaults-extra-file=%%s %s 2>/dev/null', $this->getBinary($compression), $filename, $mysqlBinary, $this->connection['database']);
        }

        //No compression
        return sprintf('cat %s | %s --defaults-extra-file=%%s %s 2>/dev/null', $filename, $mysqlBinary, $this->connection['database']);
    }

    /**
     * Stores the authentication data, to be used to import the database, in a
     *  temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @return string Path of the temporary file
     * @uses $connection
     */
    private function getImportStoreAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[client]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->connection['username'],
            empty($this->connection['password']) ? null : $this->connection['password'],
            $this->connection['host']
        ));

        return $auth;
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses $connection
     * @uses getExportExecutable()
     * @uses getExportStoreAuth()
     */
    public function export($filename)
    {
        //Stores the authentication data in a temporary file
        $auth = $this->getExportStoreAuth();

        //Executes
        exec(sprintf($this->getExportExecutable($filename), $auth), $output, $returnVar);

        //Deletes the temporary file with the authentication data
        unlink($auth);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('mysql_backup', '{0} failed with exit code `{1}`', 'mysqldump', $returnVar));
        }

        return file_exists($filename);
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses $connection
     * @uses getImportExecutable()
     * @uses getImportStoreAuth()
     */
    public function import($filename)
    {
        //Stores the authentication data in a temporary file
        $auth = $this->getImportStoreAuth();

        //Executes
        exec(sprintf($this->getImportExecutable($filename), $auth), $output, $returnVar);

        //Deletes the temporary file with the authentication data
        unlink($auth);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('mysql_backup', '{0} failed with exit code `{1}`', 'mysql', $returnVar));
        }

        return true;
    }
}
