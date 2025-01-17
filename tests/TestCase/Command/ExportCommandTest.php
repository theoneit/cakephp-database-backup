<?php
/** @noinspection PhpUnhandledExceptionInspection */
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
namespace DatabaseBackup\Test\TestCase\Command;

use DatabaseBackup\TestSuite\CommandTestCase;

/**
 * ExportCommandTest class
 */
class ExportCommandTest extends CommandTestCase
{
    /**
     * @var string
     */
    protected string $command = 'database_backup.export -v';

    /**
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecute(): void
    {
        $this->exec($this->command);
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertErrorEmpty();

        //With an invalid option value
        $this->exec($this->command . ' --filename /noExistingDir/backup.sql');
        $this->assertExitError();
    }

    /**
     * Test for `execute()` method, with `compression` option
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteCompressionOption(): void
    {
        $this->exec($this->command . ' --compression bzip2');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql\.bz2` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `filename` option
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteFilenameOption(): void
    {
        $this->exec($this->command . ' --filename backup.sql');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup.sql` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `rotate` option
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteRotateOption(): void
    {
        $files = createSomeBackups();
        $this->exec($this->command . ' --rotate 3 -v');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputContains('Backup `' . basename(array_value_first($files)) . '` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `send` option
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteSendOption(): void
    {
        $this->exec($this->command . ' --send mymail@example.com');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` was sent via mail/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `timeout` option
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteTimeoutOption(): void
    {
        $this->exec($this->command . ' --timeout 10');
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }
}
