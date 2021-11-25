<?php
declare(strict_types=1);

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

use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->Driver instanceof Mysql) {
            $this->markTestSkipped('Skipping tests for Mysql, current driver is ' . $this->Driver->getDriverName());
        }
    }

    /**
     * Test for `afterExport()` method
     * @test
     */
    public function testAfterExport(): void
    {
        $Driver = $this->getMockForDriver(Mysql::class, ['deleteAuthFile']);
        $Driver->expects($this->once())->method('deleteAuthFile');
        $Driver->afterExport();
    }

    /**
     * Test for `afterImport()` method
     * @test
     */
    public function testAfterImport(): void
    {
        $Driver = $this->getMockForDriver(Mysql::class, ['deleteAuthFile']);
        $Driver->expects($this->once())->method('deleteAuthFile');
        $Driver->afterImport();
    }

    /**
     * Test for `beforeExport()` method
     * @test
     */
    public function testBeforeExport(): void
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));
        $this->assertTrue($this->Driver->beforeExport());

        $expected = '[mysqldump]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=' . $this->Driver->getConfig('host');
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));

        @unlink($auth);
    }

    /**
     * Test for `beforeImport()` method
     * @test
     */
    public function testBeforeImport(): void
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));
        $this->assertTrue($this->Driver->beforeImport());

        $expected = '[client]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=' . $this->Driver->getConfig('host');
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));
    }

    /**
     * Test for `deleteAuthFile()` method
     * @test
     */
    public function testDeleteAuthFile(): void
    {
        $this->assertFalse($this->invokeMethod($this->Driver, 'deleteAuthFile'));

        $auth = tempnam(sys_get_temp_dir(), 'auth') ?: '';
        $this->setProperty($this->Driver, 'auth', $auth);
        $this->assertFileExists($auth);
        $this->assertTrue($this->invokeMethod($this->Driver, 'deleteAuthFile'));
        $this->assertFileDoesNotExist($auth);
    }
}
