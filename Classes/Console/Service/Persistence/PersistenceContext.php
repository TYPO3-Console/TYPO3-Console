<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service\Persistence;

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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class PersistenceContext implements PersistenceContextInterface
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var array
     */
    private $persistenceConfiguration = [];

    public function __construct(ConnectionPool $connectionPool, array $persistenceConfiguration = [])
    {
        $this->connectionPool = $connectionPool;
        $this->persistenceConfiguration = $persistenceConfiguration ?: $GLOBALS['TCA'];
    }

    /**
     * @param string $tableName
     * @throws TableDoesNotExistException
     * @return \Traversable
     */
    public function getAllRecordsOfTable($tableName)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
            $queryBuilder->select('uid', 't3ver_wsid');
        } else {
            $queryBuilder->select('uid');
        }
        try {
            return $queryBuilder
                ->from($tableName)
                ->execute();
        } catch (DBALException $e) {
            throw new TableDoesNotExistException(sprintf('Table "%s" exists in $TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.', $tableName), 1495562885, $e);
        }
    }

    /**
     * @return int
     */
    public function countAllRecordsOfAllTables()
    {
        $rowsCount = 0;
        foreach ($this->persistenceConfiguration as $tableName => $_) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            try {
                $rowsCount += $queryBuilder
                    ->count('uid')
                    ->from($tableName)
                    ->execute()
                    ->fetchColumn(0);
            } catch (DBALException $e) {
                continue;
            }
        }

        return $rowsCount;
    }

    /**
     * @param string $tableName
     * @throws TableDoesNotExistException
     * @return int
     */
    public function countLostIndexesOfRecordsInTable($tableName)
    {
        // Main query to find lost records
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                ),
                'NOT EXISTS (' . $this->getSubQueryForRecordsInIndex($tableName) . ')'
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * @param string $tableName
     * @throws \Helhum\Typo3Console\Service\Persistence\TableDoesNotExistException
     * @return bool
     */
    public function deleteLostIndexesOfRecordsInTable($tableName)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');

        return $queryBuilder->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                ),
                'NOT EXISTS (' . $this->getSubQueryForRecordsInIndex($tableName) . ')'
            )
            ->execute();
    }

    /**
     * @param $tableName
     * @throws TableDoesNotExistException
     * @return string
     */
    private function getSubQueryForRecordsInIndex($tableName)
    {
        $refIndexConnectionName = empty($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['sys_refindex']) ? ConnectionPool::DEFAULT_CONNECTION_NAME : $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['sys_refindex'];
        $tableConnectionName = empty($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName]) ? ConnectionPool::DEFAULT_CONNECTION_NAME : $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName];

        // Subselect based queries only work on the same connection
        if ($refIndexConnectionName !== $tableConnectionName) {
            throw new TableDoesNotExistException(
                sprintf(
                    'Not checking table "%s" for lost indexes, "sys_refindex" table uses a different connection',
                    $tableName
                )
            );
        }

        // Searching for lost indexes for this table
        // Build sub-query to find lost records
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid')
            ->from($tableName, 'sub_' . $tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'sub_' . $tableName . '.uid',
                    $queryBuilder->quoteIdentifier('sys_refindex.recuid')
                )
            );

        return $queryBuilder->getSQL();
    }

    /**
     * @param array $processedTables
     * @return int
     */
    public function countLostTables(array $processedTables)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn(
                    'tablename',
                    $queryBuilder->createNamedParameter($processedTables, Connection::PARAM_STR_ARRAY)
                )
            )->execute()
            ->fetchColumn(0);
    }

    /**
     * @param array $processedTables
     */
    public function deleteLostTables(array $processedTables)
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn(
                    'tablename',
                    $queryBuilder->createNamedParameter($processedTables, Connection::PARAM_STR_ARRAY)
                )
            )->execute();
    }

    /**
     * @return array
     */
    public function getPersistenceConfiguration()
    {
        return $this->persistenceConfiguration;
    }
}
