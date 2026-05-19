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

use TYPO3\CMS\Core\Database\ConnectionPool;

final class ConnectionConfigurationFactory
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    public function build(string $connectionName, string $scope): MysqlCliConfiguration
    {
        return new MysqlCliConfiguration(
            $this->connectionPool->getConnectionByName($connectionName),
            $connectionName,
            $scope,
        );
    }

    public function getAvailableConnectionNames(): array
    {
        return array_filter(
            $this->connectionPool->getConnectionNames(),
            fn (string $connectionName) => str_contains($this->connectionPool->getConnectionByName($connectionName)->getParams()['driver'] ?? '', 'mysql'),
        );
    }
}
