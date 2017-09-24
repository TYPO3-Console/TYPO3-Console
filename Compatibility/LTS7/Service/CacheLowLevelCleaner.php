<?php
namespace Helhum\Typo3Console\LTS7\Service;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
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
        $cacheDirectory = PATH_site . 'typo3temp/Cache';
        GeneralUtility::flushDirectory($cacheDirectory, true);
    }

    /**
     * Truncate all DB tables prefixed with 'cf_'
     */
    public function forceFlushDatabaseCacheTables()
    {
        // Get all table names starting with 'cf_' and truncate them
        /** @var DatabaseConnection $db */
        $db = $GLOBALS['TYPO3_DB'];
        $tables = $db->admin_get_tables();
        foreach ($tables as $table) {
            $tableName = $table['Name'];
            if ($tableName === 'cache_treelist' || strpos($tableName, 'cf_') === 0) {
                $db->exec_TRUNCATEquery($tableName);
            }
        }
    }
}
