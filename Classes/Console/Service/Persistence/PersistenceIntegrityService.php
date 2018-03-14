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

use Helhum\Typo3Console\Service\Delegation\ReferenceIndexIntegrityDelegateInterface;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PersistenceIntegrityService
{
    /**
     * @var PersistenceContextInterface
     */
    private $persistenceContext;

    public function __construct(PersistenceContextInterface $persistenceContext)
    {
        $this->persistenceContext = $persistenceContext;
    }

    /**
     * Updating Reference Index
     *
     * @param ReferenceIndexIntegrityDelegateInterface|null $delegate
     * @return array Tuple ($errorCount, $recordCount, $processedTables)
     */
    public function updateReferenceIndex(ReferenceIndexIntegrityDelegateInterface $delegate = null)
    {
        return $this->checkOrUpdateReferenceIndex(false, $delegate);
    }

    /**
     * Checking Reference Index
     *
     * @param ReferenceIndexIntegrityDelegateInterface|null $delegate
     * @return array Tuple ($errorCount, $recordCount, $processedTables)
     */
    public function checkReferenceIndex(ReferenceIndexIntegrityDelegateInterface $delegate = null)
    {
        return $this->checkOrUpdateReferenceIndex(true, $delegate);
    }

    /**
     * Updating or checking Reference Index
     *
     * @param bool $dryRun
     * @param ReferenceIndexIntegrityDelegateInterface|null $delegate
     * @return array Tuple ($errorCount, $recordCount, $processedTables)
     */
    protected function checkOrUpdateReferenceIndex($dryRun, ReferenceIndexIntegrityDelegateInterface $delegate = null)
    {
        $processedTables = [];
        $errorCount = 0;
        $recordCount = 0;

        $this->callDelegateForEvent($delegate, 'willStartOperation', [$this->persistenceContext->countAllRecordsOfAllTables()]);

        // Traverse all tables:
        foreach ($this->persistenceContext->getPersistenceConfiguration() as $tableName => $_) {
            // Traverse all records in tables, including deleted records:
            try {
                $records = $this->persistenceContext->getAllRecordsOfTable($tableName);
            } catch (TableDoesNotExistException $e) {
                $this->delegateLog($delegate, 'warning', $e->getMessage());
                continue;
            }
            if (!is_array($records) && !$records instanceof \Traversable) {
                $this->delegateLog($delegate, 'error', 'Table "%s" exists in $TCA but fetching records from database failed. Check the Database Analyzer in Install Tool for missing fields.', [$tableName]);
                continue;
            }
            $processedTables[] = $tableName;
            foreach ($records as $record) {
                $this->callDelegateForEvent($delegate, 'willUpdateRecord', [$tableName, $record]);

                /** @var $refIndexObj ReferenceIndex */
                $refIndexObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ReferenceIndex::class);
                if (isset($record['t3ver_wsid'])) {
                    $refIndexObj->setWorkspaceId($record['t3ver_wsid']);
                }
                $result = $refIndexObj->updateRefIndexTable($tableName, $record['uid'], $dryRun);
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
            try {
                // Searching lost indexes for this table:
                $lostIndexCount = $this->persistenceContext->countLostIndexesOfRecordsInTable($tableName);
                if ($lostIndexCount > 0) {
                    $errorMessage = 'Table ' . $tableName . ' has ' . $lostIndexCount;
                    if ($dryRun) {
                        $errorMessage .= ' which need to be deleted.';
                    } else {
                        $errorMessage .= ' which have been deleted.';
                    }
                    $errorCount++;
                    $this->delegateLog($delegate, 'notice', $errorMessage);
                    if (!$dryRun) {
                        $this->persistenceContext->deleteLostIndexesOfRecordsInTable($tableName);
                    }
                }
            } catch (TableDoesNotExistException $e) {
                $this->delegateLog($delegate, 'warning', $e->getMessage());
                continue;
            }
        }

        // Searching lost indexes for non-existing tables:
        $lostTablesCount = $this->persistenceContext->countLostTables($processedTables);
        if ($lostTablesCount > 0) {
            $errorCount++;
            $this->delegateLog($delegate, 'notice', 'Found ' . $lostTablesCount . ' indexes for not existing tables.');
            if (!$dryRun) {
                $this->persistenceContext->deleteLostTables($processedTables);
                $this->delegateLog($delegate, 'info', 'Removed indexes for not existing tables.');
            }
        }

        $this->callDelegateForEvent($delegate, 'operationHasEnded');

        return [$errorCount, $recordCount, $processedTables];
    }

    /**
     * @param ReferenceIndexIntegrityDelegateInterface|null $delegate
     * @param string $eventName
     * @param array $arguments
     */
    protected function callDelegateForEvent($delegate, $eventName, $arguments = [])
    {
        if ($delegate === null) {
            return;
        }
        $delegate->$eventName(...$arguments);
    }

    /**
     * @param ReferenceIndexIntegrityDelegateInterface|null $delegate
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
}
