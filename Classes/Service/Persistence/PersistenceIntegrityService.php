<?php
namespace Helhum\Typo3Console\Service\Persistence;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Service\Delegation\ReferenceIndexIntegrityDelegateInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PersistenceIntegrityService
 */
class PersistenceIntegrityService
{
    /**
     * Updating Reference Index
     *
     * @param PersistenceContext $persistenceContext
     * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
     * @return array Tuple ($errorCount, $recordCount, $processedTables)
     */
    public function updateReferenceIndex(PersistenceContext $persistenceContext, ReferenceIndexIntegrityDelegateInterface $delegate = null)
    {
        return $this->checkOrUpdateReferenceIndex(false, $persistenceContext, $delegate);
    }

    /**
     * Checking Reference Index
     *
     * @param PersistenceContext $persistenceContext
     * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
     * @return array Tuple ($errorCount, $recordCount, $processedTables)
     */
    public function checkReferenceIndex(PersistenceContext $persistenceContext, ReferenceIndexIntegrityDelegateInterface $delegate = null)
    {
        return $this->checkOrUpdateReferenceIndex(true, $persistenceContext, $delegate);
    }

    /**
     * Updating or checking Reference Index
     *
     * @param bool $dryRun
     * @param PersistenceContext $persistenceContext
     * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
     * @return array Tuple ($errorCount, $recordCount, $processedTables)
     */
    protected function checkOrUpdateReferenceIndex($dryRun, PersistenceContext $persistenceContext, ReferenceIndexIntegrityDelegateInterface $delegate = null)
    {
        $processedTables = [];
        $errorCount = 0;
        $recordCount = 0;

        $existingTableNames = $persistenceContext->getDatabaseConnection()->admin_get_tables();

        $this->callDelegateForEvent($delegate, 'willStartOperation', [$this->countRowsOfAllRegisteredTables($persistenceContext, $existingTableNames)]);

        // Traverse all tables:
        foreach (array_keys($persistenceContext->getPersistenceConfiguration()) as $tableName) {
            // Traverse all records in tables, including deleted records:
            $selectFields = (BackendUtility::isTableWorkspaceEnabled($tableName) ? 'uid,t3ver_wsid' : 'uid');
            if (!empty($existingTableNames[$tableName])) {
                $records = $persistenceContext->getDatabaseConnection()->exec_SELECTgetRows($selectFields, $tableName, '1=1');
            } else {
                $this->delegateLog($delegate, 'warning', 'Table "%s" exists in $TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.', [$tableName]);
                continue;
            }
            if (!is_array($records)) {
                $this->delegateLog($delegate, 'error', 'Table "%s" exists in $TCA but fetching records from database failed. Check the Database Analyzer in Install Tool for missing fields.', [$tableName]);
                continue;
            }
            $processedTables[] = $tableName;
            $uidList = [0];
            foreach ($records as $record) {
                $this->callDelegateForEvent($delegate, 'willUpdateRecord', [$tableName, $record]);

                /** @var $refIndexObj ReferenceIndex */
                $refIndexObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
                if (isset($record['t3ver_wsid'])) {
                    $refIndexObj->setWorkspaceId($record['t3ver_wsid']);
                }
                $result = $refIndexObj->updateRefIndexTable($tableName, $record['uid'], $dryRun);
                $uidList[] = $record['uid'];
                $recordCount++;
                if ($result['addedNodes'] || $result['deletedNodes']) {
                    $errorMessage = 'Record ' . $tableName . ':' . $record['uid'];
                    if ($dryRun) {
                        $errorMessage .= ' has ' . $result['addedNodes'] . ' missing indexes and ' . $result['deletedNodes'] . ' stale indexes.';
                    } else {
                        $errorMessage .= ' ' . $result['addedNodes'] . ' indexes were added and ' . $result['deletedNodes'] . ' stale indexes were removed.';
                    }
                    $errorCount++;
                    $this->delegateLog($delegate, 'notice', $errorMessage);
                }
            }
            // Searching lost indexes for this table:
            $where = 'tablename=' . $persistenceContext->getDatabaseConnection()->fullQuoteStr($tableName, 'sys_refindex') . ' AND recuid NOT IN (' . implode(',', $uidList) . ')';
            $lostIndexes = $persistenceContext->getDatabaseConnection()->exec_SELECTgetRows('hash', 'sys_refindex', $where);
            if (count($lostIndexes)) {
                $errorMessage = 'Table ' . $tableName . ' has ' . count($lostIndexes);
                if ($dryRun) {
                    $errorMessage .= ' which need to be deleted.';
                } else {
                    $errorMessage .= ' which have been deleted.';
                }
                $errorCount++;
                $this->delegateLog($delegate, 'notice', $errorMessage);
                if (!$dryRun) {
                    $persistenceContext->getDatabaseConnection()->exec_DELETEquery('sys_refindex', $where);
                }
            }
        }

        // Searching lost indexes for non-existing tables:
        $where = 'tablename NOT IN (' . implode(',', $persistenceContext->getDatabaseConnection()->fullQuoteArray($processedTables, 'sys_refindex')) . ')';
        $lostTablesCount = $persistenceContext->getDatabaseConnection()->exec_SELECTcountRows('hash', 'sys_refindex', $where);
        if ($lostTablesCount) {
            $errorCount++;
            $this->delegateLog($delegate, 'notice', 'Found ' . $lostTablesCount . ' indexes for not existing tables.');
            if (!$dryRun) {
                $persistenceContext->getDatabaseConnection()->exec_DELETEquery('sys_refindex', $where);
                $this->delegateLog($delegate, 'info', 'Removed indexes for not existing tables.');
            }
        }

        $this->callDelegateForEvent($delegate, 'operationHasEnded');

        return [$errorCount, $recordCount, $processedTables];
    }

    /**
     * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
     * @param string $eventName
     * @param array $arguments
     */
    protected function callDelegateForEvent($delegate, $eventName, $arguments = [])
    {
        if ($delegate === null) {
            return;
        }
        call_user_func_array([$delegate, $eventName], $arguments);
    }

    /**
     * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
     * @param string $severity
     * @param string $message
     * @param array $data
     */
    protected function delegateLog($delegate, $severity, $message, $data = [])
    {
        if ($delegate === null) {
            return;
        }
        $delegate->getLogger()->{$severity}($message, $data);
    }

    /**
     * @param PersistenceContext $persistenceContext
     * @param array $existingTableNames
     * @return int
     */
    protected function countRowsOfAllRegisteredTables(PersistenceContext $persistenceContext, $existingTableNames)
    {
        $rowsCount = 0;
        foreach (array_keys($persistenceContext->getPersistenceConfiguration()) as $tableName) {
            if (empty($existingTableNames[$tableName])) {
                continue;
            }
            $rowsCount += $persistenceContext->getDatabaseConnection()->exec_SELECTcountRows('uid', $tableName, '1=1');
        }

        return $rowsCount;
    }
}
