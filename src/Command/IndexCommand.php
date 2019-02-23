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
 * @since       2.6.0
 */
namespace DatabaseBackup\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Number;
use Cake\ORM\Entity;
use DatabaseBackup\Console\Command;
use DatabaseBackup\Utility\BackupManager;

/**
 * Lists database backups
 */
class IndexCommand extends Command
{
    /**
     * Hook method for defining this command's option parser
     * @param ConsoleOptionParser $parser The parser to be defined
     * @return ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        return $parser->setDescription(__d('database_backup', 'Lists database backups'));
    }

    /**
     * Lists database backups
     * @param Arguments $args The command arguments
     * @param ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#index
     * @uses DatabaseBackup\Utility\BackupManager::index()
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);

        //Gets all backups
        $backups = (new BackupManager)->index();
        $io->out(__d('database_backup', 'Backup files found: {0}', $backups->count()));

        if ($backups->isEmpty()) {
            return null;
        }

        $headers = [
            __d('database_backup', 'Filename'),
            __d('database_backup', 'Extension'),
            __d('database_backup', 'Compression'),
            __d('database_backup', 'Size'),
            __d('database_backup', 'Datetime'),
        ];
        $cells = $backups->map(function (Entity $backup) {
            return $backup->set('size', Number::toReadableSize($backup->size))->toArray();
        });
        $io->helper('table')->output(array_merge([$headers], $cells->toList()));

        return null;
    }
}