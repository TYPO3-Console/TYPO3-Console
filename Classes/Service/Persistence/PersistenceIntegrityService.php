<?php
namespace Helhum\Typo3Console\Service\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Service\Delegation\ReferenceIndexIntegrityDelegateInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PersistenceIntegrityService
 */
class PersistenceIntegrityService {

	/**
	 * Updating Reference Index
	 *
	 * @param PersistenceContext $persistenceContext
	 * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
	 * @return array Tuple ($errorCount, $recordCount, $processedTables)
	 */
	public function updateReferenceIndex(PersistenceContext $persistenceContext, ReferenceIndexIntegrityDelegateInterface $delegate = NULL) {
		return $this->checkOrUpdateReferenceIndex(FALSE, $persistenceContext, $delegate);
	}

	/**
	 * Checking Reference Index
	 *
	 * @param PersistenceContext $persistenceContext
	 * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
	 * @return array Tuple ($errorCount, $recordCount, $processedTables)
	 */
	public function checkReferenceIndex(PersistenceContext $persistenceContext, ReferenceIndexIntegrityDelegateInterface $delegate = NULL) {
		return $this->checkOrUpdateReferenceIndex(TRUE, $persistenceContext, $delegate);
	}

	/**
	 * Updating or checking Reference Index
	 *
	 * @param bool $dryRun
	 * @param PersistenceContext $persistenceContext
	 * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
	 * @return array Tuple ($errorCount, $recordCount, $processedTables)
	 */
	protected function checkOrUpdateReferenceIndex($dryRun, PersistenceContext $persistenceContext, ReferenceIndexIntegrityDelegateInterface $delegate = NULL) {
		$processedTables = array();
		$errorCount = 0;
		$recordCount = 0;

		$existingTableNames = $persistenceContext->getDatabaseConnection()->admin_get_tables();

		$this->callDelegateForEvent($delegate, 'willStartOperation', array($this->countRowsOfAllRegisteredTables($persistenceContext, $existingTableNames)));

		// Traverse all tables:
		foreach (array_keys($persistenceContext->getPersistenceConfiguration()) as $tableName) {
			// Traverse all records in tables, including deleted records:
			$selectFields = (BackendUtility::isTableWorkspaceEnabled($tableName) ? 'uid,t3ver_wsid' : 'uid');
			if (!empty($existingTableNames[$tableName])) {
				$records = $persistenceContext->getDatabaseConnection()->exec_SELECTgetRows($selectFields, $tableName, '1=1');
			} else {
				$this->delegateLog($delegate, 'warning', 'Table "%s" exists in $TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.', array($tableName));
				continue;
			}
			if (!is_array($records)) {
				$this->delegateLog($delegate, 'error', 'Table "%s" exists in $TCA but fetching records from database failed. Check the Database Analyzer in Install Tool for missing fields.', array($tableName));
				continue;
			}
			$processedTables[] = $tableName;
			$uidList = array(0);
			foreach ($records as $record) {
				$this->callDelegateForEvent($delegate, 'willUpdateRecord', array($tableName, $record));

				/** @var $refIndexObj ReferenceIndex */
				$refIndexObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
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

		return array($errorCount, $recordCount, $processedTables);
	}

	/**
	 * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
	 * @param string $eventName
	 * @param array $arguments
	 */
	protected function callDelegateForEvent($delegate, $eventName, $arguments = array()) {
		if ($delegate === NULL) {
			return;
		}
		call_user_func_array(array($delegate, $eventName), $arguments);
	}

	/**
	 * @param ReferenceIndexIntegrityDelegateInterface|NULL $delegate
	 * @param string $severity
	 * @param string $message
	 * @param array $data
	 */
	protected function delegateLog($delegate, $severity, $message, $data = array()) {
		if ($delegate === NULL) {
			return;
		}
		$delegate->getLogger()->{$severity}($message, $data);
	}

	/**
	 * @param PersistenceContext $persistenceContext
	 * @param array $existingTableNames
	 * @return int
	 */
	protected function countRowsOfAllRegisteredTables(PersistenceContext $persistenceContext, $existingTableNames) {
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
