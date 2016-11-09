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
namespace MysqlBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MysqlBackup\Utility\BackupExport as BaseBackupExport;

/**
 * Makes public some protected methods/properties from `BackupExport`
 */
class BackupExport extends BaseBackupExport
{
    public function getAuth()
    {
        return $this->_storeAuth();
    }

    public function getCompression()
    {
        return $this->compression;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getExecutable()
    {
        return $this->executable;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getRotate()
    {
        return $this->rotate;
    }
}

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $instance = new BackupExport();

        $connection = $instance->getConnection();
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');

        $filename = $instance->getFilename();
        $this->assertEquals(Configure::read('MysqlBackup.target'), dirname($filename));
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql/', basename($filename));

        $this->assertFalse($instance->getCompression());
    }

    /**
     * Test for `compression()` method
     * @test
     */
    public function testCompression()
    {
        $instance = new BackupExport();

        $this->assertFalse($instance->getCompression());
        $this->assertEquals('/usr/bin/mysqldump --defaults-file=%s %s > %s', $instance->getExecutable());

        $instance->compression('bzip2');
        $this->assertEquals('bzip2', $instance->getCompression());
        $this->assertEquals('/usr/bin/mysqldump --defaults-file=%s %s | /bin/bzip2 > %s', $instance->getExecutable());

        $instance->compression('gzip');
        $this->assertEquals('gzip', $instance->getCompression());
        $this->assertEquals('/usr/bin/mysqldump --defaults-file=%s %s | /bin/gzip > %s', $instance->getExecutable());

        $instance->compression(false);
        $this->assertFalse($instance->getCompression());
        $this->assertEquals('/usr/bin/mysqldump --defaults-file=%s %s > %s', $instance->getExecutable());
    }

    /**
     * Test for `compression()` method, with an invalid stringvalue
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidString()
    {
        (new BackupExport())->compression('invalidValue');
    }

    /**
     * Test for `compression()` method, with an invalid boolean value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidBool()
    {
        (new BackupExport())->compression(true);
    }

    /**
     * Test for `filename()` method
     * @test
     */
    public function testFilename()
    {
        $instance = new BackupExport();

        $instance->filename('/backup.sql');
        $this->assertEquals('/backup.sql', $instance->getFilename());
        $this->assertFalse($instance->getCompression());

        $instance->filename('/backup.sql.gz');
        $this->assertEquals('/backup.sql.gz', $instance->getFilename());
        $this->assertEquals('gzip', $instance->getCompression());

        $instance->filename('/backup.sql.bz2');
        $this->assertEquals('/backup.sql.bz2', $instance->getFilename());
        $this->assertEquals('bzip2', $instance->getCompression());

        //Relative path
        $instance->filename('backup.sql');
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql', $instance->getFilename());
        $this->assertFalse($instance->getCompression());
    }

    /**
     * Test for `filename()` method, with patterns
     * @test
     */
    public function testFilenameWithPatterns()
    {
        $instance = new BackupExport();

        $instance->filename('/{$DATABASE}.sql');
        $this->assertEquals('/test.sql', $instance->getFilename());

        $instance->filename('/{$DATETIME}.sql');
        $this->assertEquals('/' . date('YmdHis') . '.sql', $instance->getFilename());

        $instance->filename('/{$HOSTNAME}.sql');
        $this->assertEquals('/localhost.sql', $instance->getFilename());

        $instance->filename('/{$TIMESTAMP}.sql');
        $this->assertEquals('/' . time() . '.sql', $instance->getFilename());
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid file extension
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        (new BackupExport())->filename('/backup.txt');
    }

    /**
     * Test for `filename()` method, without extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid file extension
     * @test
     */
    public function testFilenameWithoutExtension()
    {
        (new BackupExport())->filename('/backup');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $instance = new BackupExport();

        $instance->rotate(10);
        $this->assertEquals(10, $instance->getRotate());
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid rotate value
     * @test
     */
    public function testRotateWithInvalidValue()
    {
        (new BackupExport())->rotate(-1);
    }

    /**
     * Test for `_storeAuth()` method
     * @test
     */
    public function testStoreAuth()
    {
        $auth = (new BackupExport())->getAuth();

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[mysqldump]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        (new BackupExport())->export();
    }
}
