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
 */
namespace MysqlBackup\Driver;

use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Driver;

/**
 * Sqlite driver to export/import database backups
 */
class Sqlite extends Driver
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
        $sqlite3Binary = $this->getBinary('sqlite3');

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            return sprintf('%s %s .dump | %s > %s 2>/dev/null', $sqlite3Binary, $this->connection['database'], $this->getBinary($compression), $filename);
        }

        //No compression
        return sprintf('%s %s .dump > %s 2>/dev/null', $sqlite3Binary, $this->connection['database'], $filename);
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
        $sqlite3Binary = $this->getBinary('sqlite3');

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            return sprintf('%s -dc %s | %s %s', $this->getBinary($compression), $filename, $sqlite3Binary, $this->connection['database']);
        }

        //No compression
        return sprintf('%s %s < %s 2>/dev/null', $sqlite3Binary, $this->connection['database'], $filename);
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @uses getExportExecutable()
     */
    public function export($filename)
    {
        //Executes
        exec($this->getExportExecutable($filename), $output, $returnVar);

        return file_exists($filename);
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @uses deleteAllRecords()
     * @uses getImportExecutable()
     */
    public function import($filename)
    {
        $this->deleteAllRecords();

        //Executes
        exec($this->getImportExecutable($filename), $output, $returnVar);

        return true;
    }
}
