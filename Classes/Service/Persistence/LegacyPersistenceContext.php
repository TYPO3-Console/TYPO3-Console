<?php
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

class LegacyPersistenceContext implements PersistenceContextInterface
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var array
     */
    protected $persistenceConfiguration = [];

    public function __construct(DatabaseConnection $databaseConnection = null, array $persistenceConfiguration = [])
    {
        $this->databaseConnection = $databaseConnection ?: $GLOBALS['TYPO3_DB'];
        $this->persistenceConfiguration = $persistenceConfiguration ?: $GLOBALS['TCA'];
    }

    /**
     * @param string $tableName
     * @throws TableDoesNotExistException
     * @return array
     */
    public function getAllRecordsOfTable($tableName)
    {
        $existingTableNames = $this->databaseConnection->admin_get_tables();
        if (empty($existingTableNames[$tableName])) {
            throw new TableDoesNotExistException(sprintf('Table "%s" exists in $TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.', $tableName), 1495562885);
        }
        $selectFields = (BackendUtility::isTableWorkspaceEnabled($tableName) ? 'uid,t3ver_wsid' : 'uid');
        return $this->databaseConnection->exec_SELECTgetRows($selectFields, $tableName, '1=1');
    }

    /**
     * @return int
     */
    public function countAllRecordsOfAllTables()
    {
        $rowsCount = 0;
        $existingTableNames = $this->databaseConnection->admin_get_tables();
        foreach ($this->persistenceConfiguration as $tableName => $_) {
            if (empty($existingTableNames[$tableName])) {
                continue;
            }
            $rowsCount += $this->databaseConnection->exec_SELECTcountRows('uid', $tableName, '1=1');
        }

        return $rowsCount;
    }

    /**
     * @param string $tableName
     * @param array $recordIds
     * @throws \InvalidArgumentException
     * @return int
     */
    public function countLostIndexesOfRecordsInTable($tableName, array $recordIds = [])
    {
        return $this->databaseConnection->exec_SELECTcountRows(
            'hash',
            'sys_refindex',
            'tablename=' . $this->databaseConnection->fullQuoteStr($tableName, 'sys_refindex')
            . ' AND recuid NOT IN (' . implode(',', array_map('intval', $recordIds)) . ')'
        );
    }

    /**
     * @param string $tableName
     * @param array $recordIds
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function deleteLostIndexesOfRecordsInTable($tableName, array $recordIds = [])
    {
        return $this->databaseConnection->exec_DELETEquery(
            'sys_refindex',
            'tablename=' . $this->databaseConnection->fullQuoteStr($tableName, 'sys_refindex')
            . ' AND recuid NOT IN (' . implode(',', array_map('intval', $recordIds)) . ')'
        );
    }

    /**
     * @param array $processedTables
     * @return int
     */
    public function countLostTables(array $processedTables)
    {
        return $this->databaseConnection->exec_SELECTcountRows(
            'hash',
            'sys_refindex',
            'tablename NOT IN (' . implode(',', $this->databaseConnection->fullQuoteArray($processedTables, 'sys_refindex')) . ')'
        );
    }

    /**
     * @param array $processedTables
     */
    public function deleteLostTables(array $processedTables)
    {
        $this->databaseConnection->exec_DELETEquery(
            'sys_refindex',
            'tablename NOT IN (' . implode(',', $this->databaseConnection->fullQuoteArray($processedTables, 'sys_refindex')) . ')'
        );
    }

    /**
     * @return array
     */
    public function getPersistenceConfiguration()
    {
        return $this->persistenceConfiguration;
    }
}
