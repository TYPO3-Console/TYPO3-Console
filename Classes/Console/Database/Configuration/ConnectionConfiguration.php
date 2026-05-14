<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Database\Configuration;

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

use Helhum\Typo3Console\Database\Schema\TableMatcher;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConnectionConfiguration
{
    /**
     * Returns a normalized DB configuration array
     */
    public function build(string $connectionName = 'Default'): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName($connectionName);

        return $connection->getParams();
    }

    public function getAvailableConnectionNames(string $type): array
    {
        return array_filter(
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames(),
            function (string $connectionName) use ($type) {
                $driverName = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName($connectionName)->getParams()['driver'] ?? '';

                return strpos($driverName, $type) !== false;
            }
        );
    }

    public function matchTables(array $excludes, string $connectionName): array
    {
        if (empty($excludes)) {
            return [];
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName($connectionName);

        return (new TableMatcher())->match($connection, ...$excludes);
    }
}
