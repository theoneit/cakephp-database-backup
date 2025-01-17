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
namespace DatabaseBackup;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Tools\Filesystem;

/**
 * A trait that provides some methods used by all other classes
 */
trait BackupTrait
{
    /**
     * Returns the absolute path for a backup file
     * @param string $path Relative or absolute path
     * @return string
     */
    public static function getAbsolutePath(string $path): string
    {
        return Filesystem::makePathAbsolute($path, Configure::readOrFail('DatabaseBackup.target'));
    }

    /**
     * Returns the compression type for a backup file
     * @param string $path File path
     * @return string|null Compression type or `null`
     */
    public static function getCompression(string $path): ?string
    {
        $extension = self::getExtension($path);

        return self::getValidCompressions()[$extension] ?? null;
    }

    /**
     * Gets the `Connection` instance.
     *
     * You can pass the name of the connection. By default, the connection set in the configuration will be used.
     * @param string|null $name Connection name
     * @return \Cake\Datasource\ConnectionInterface
     */
    public static function getConnection(?string $name = null): ConnectionInterface
    {
        return ConnectionManager::get($name ?: Configure::readOrFail('DatabaseBackup.connection'));
    }

    /**
     * Gets the driver name, according to the connection
     * @return string Driver name
     * @throws \ReflectionException
     * @since 2.9.2
     */
    public static function getDriverName(): string
    {
        return get_class_short_name(self::getConnection()->getDriver());
    }

    /**
     * Takes and gets the extension of a backup file
     * @param string $path File path
     * @return string|null Extension or `null` for invalid extensions
     */
    public static function getExtension(string $path): ?string
    {
        $extension = Filesystem::getExtension($path);

        return in_array($extension, array_keys(DATABASE_BACKUP_EXTENSIONS)) ? $extension : null;
    }

    /**
     * Returns all valid compressions available
     * @return array<string, string> An array with extensions as keys and compressions as values
     * @since 2.4.0
     */
    public static function getValidCompressions(): array
    {
        return array_filter(DATABASE_BACKUP_EXTENSIONS);
    }
}
