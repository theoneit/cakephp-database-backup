<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace DatabaseBackup\Test\TestCase\Driver;

use Cake\Database\Connection;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    /**
     * @var \DatabaseBackup\Driver\Mysql
     */
    protected $DriverClass = Mysql::class;

    /**
     * Name of the database connection
     * @var string
     */
    protected $connection = 'test';

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');

        $expected = sprintf('%s --defaults-file=%s test', $this->getBinary('mysqldump'), escapeshellarg('authFile'));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');

        $expected = sprintf('%s --defaults-extra-file=%s test', $this->getBinary('mysql'), escapeshellarg('authFile'));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
    }

    /**
     * Test for `afterExport()` method
     * @test
     */
    public function testAfterExport()
    {
        $this->Driver = $this->getMockBuilder(Mysql::class)
            ->setMethods(['deleteAuthFile'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->expects($this->once())
            ->method('deleteAuthFile');

        $this->Driver->afterExport();
    }

    /**
     * Test for `afterImport()` method
     * @test
     */
    public function testAfterImport()
    {
        $this->Driver = $this->getMockBuilder(Mysql::class)
            ->setMethods(['deleteAuthFile'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->expects($this->once())
            ->method('deleteAuthFile');

        $this->Driver->afterImport();
    }

    /**
     * Test for `beforeExport()` method
     * @test
     */
    public function testBeforeExport()
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));
        $this->Driver->beforeExport();

        $expected = '[mysqldump]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=localhost';
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));

        safe_unlink($auth);
    }

    /**
     * Test for `beforeImport()` method
     * @test
     */
    public function testBeforeImport()
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));

        $this->Driver->beforeImport();

        $expected = '[client]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=localhost';
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));
    }

    /**
     * Test for `deleteAuthFile()` method
     * @test
     */
    public function testDeleteAuthFile()
    {
        $this->assertFalse($this->invokeMethod($this->Driver, 'deleteAuthFile'));

        //Creates auth file
        $auth = tempnam(sys_get_temp_dir(), 'auth');
        $this->setProperty($this->Driver, 'auth', $auth);

        $this->assertFileExists($auth);
        $this->assertTrue($this->invokeMethod($this->Driver, 'deleteAuthFile'));
        $this->assertFileNotExists($auth);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed with exit code `2`
     * @test
     */
    public function testExportOnFailure()
    {
        //Sets a no existing database
        $config = array_merge($this->Driver->getConfig(), ['database' => 'noExisting']);
        $this->setProperty($this->Driver, 'connection', new Connection($config));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Driver->export($backup);

        //Sets a no existing database
        $config = array_merge($this->Driver->getConfig(), ['database' => 'noExisting']);
        $this->setProperty($this->Driver, 'connection', new Connection($config));

        $this->Driver->import($backup);
    }
}