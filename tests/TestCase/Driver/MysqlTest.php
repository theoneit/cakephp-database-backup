<?php
/** @noinspection PhpDocMissingThrowsInspection, PhpUnhandledExceptionInspection */
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
use Tools\ReflectionTrait;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    use ReflectionTrait;

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
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::afterExport()
     */
    public function testAfterExport(): void
    {
        $Driver = $this->createPartialMock(Mysql::class, ['deleteAuthFile']);
        $Driver->expects($this->once())->method('deleteAuthFile');
        $Driver->afterExport();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::afterImport()
     */
    public function testAfterImport(): void
    {
        $Driver = $this->createPartialMock(Mysql::class, ['deleteAuthFile']);
        $Driver->expects($this->once())->method('deleteAuthFile');
        $Driver->afterImport();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::beforeExport()
     */
    public function testBeforeExport(): void
    {
        $this->assertTrue($this->Driver->beforeExport());

        $expected = '[mysqldump]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=' . $this->Driver->getConfig('host');
        $auth = $this->invokeMethod($this->Driver, 'getAuthFile');
        $this->assertStringEqualsFile($auth, $expected);

        unlink($auth);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::beforeImport()
     */
    public function testBeforeImport(): void
    {
        $this->assertTrue($this->Driver->beforeImport());

        $expected = '[client]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=' . $this->Driver->getConfig('host');
        $auth = $this->invokeMethod($this->Driver, 'getAuthFile');
        $this->assertStringEqualsFile($auth, $expected);

        unlink($auth);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::deleteAuthFile()
     */
    public function testDeleteAuthFile(): void
    {
        $this->assertFalse($this->invokeMethod($this->Driver, 'deleteAuthFile'));

        $auth = tempnam(sys_get_temp_dir(), 'auth') ?: '';
        $Driver = $this->createPartialMock(Mysql::class, ['getAuthFile']);
        $Driver->method('getAuthFile')->willReturn($auth);
        $this->assertFileExists($auth);
        $this->assertTrue($this->invokeMethod($Driver, 'deleteAuthFile'));
        $this->assertFileDoesNotExist($auth);
    }
}
