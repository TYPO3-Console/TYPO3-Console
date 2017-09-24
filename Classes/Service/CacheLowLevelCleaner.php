<?php
namespace Helhum\Typo3Console\Service;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Flushes caches low level
 */
class CacheLowLevelCleaner
{
    /**
     * Recursively delete cache directory
     */
    public function forceFlushCachesFiles()
    {
        $cacheDirectory = PATH_site . 'typo3temp/var/Cache';
        GeneralUtility::flushDirectory($cacheDirectory, true);
    }

    /**
     * Truncate all DB tables prefixed with 'cf_'
     */
    public function forceFlushDatabaseCacheTables()
    {
        // Get all table names from Default connection starting with 'cf_' and truncate them
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $tablesNames = $tableNames = $connection->getSchemaManager()->listTableNames();
        foreach ($tablesNames as $tableName) {
            if ($tableName === 'cache_treelist' || strpos($tableName, 'cf_') === 0) {
                $connection->truncate($tableName);
            }
        }
    }
}
