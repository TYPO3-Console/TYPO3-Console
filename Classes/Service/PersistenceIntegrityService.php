<?php
namespace Helhum\Typo3Console\Service;

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

use Helhum\Typo3Console\Context\NotificationContext;
use Helhum\Typo3Console\Context\PersistenceContext;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PersistenceIntegrityService
 */
class PersistenceIntegrityService {


	public function updateReferenceIndex() {
		
	}

	/**
	 * Updating or checking Reference Index
	 *
	 * @param PersistenceContext $persitenceContext
	 * @param NotificationContext $notificationContext
	 * @return array Tuple ($errorCount, $recordCount, $processedTables)
	 */
	public function checkOrUpdateReferenceIndex(PersistenceContext $persitenceContext, NotificationContext $notificationContext) {
		$processedTables = array();
		$errorCount = 0;
		$recordCount = 0;
		$dryRun = (bool)$persitenceContext->getOption('dry-run');

		$existingTableNames = $persitenceContext->getDatabaseConnection()->admin_get_tables();

		$notificationContext->emitEvent('startOperation', array($this->countRowsOfAllRegisteredTables($persitenceContext, $existingTableNames)));

		// Traverse all tables:
		foreach (array_keys($persitenceContext->getPersistenceConfiguration()) as $tableName) {
			// Traverse all records in tables, including deleted records:
			$selectFields = (BackendUtility::isTableWorkspaceEnabled($tableName) ? 'uid,t3ver_wsid' : 'uid');
			if (!empty($existingTableNames[$tableName])) {
				$records = $persitenceContext->getDatabaseConnection()->exec_SELECTgetRows($selectFields, $tableName, '1=1');
			} else {
				$notificationContext->getLogger()->warning('Table "%s" exists in $TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.', array($tableName));
				continue;
			}
			if (!is_array($records)) {
				$notificationContext->getLogger()->error('Table "%s" exists in $TCA but fetching records from database failed. Check the Database Analyzer in Install Tool for missing fields.', array($tableName));
				continue;
			}
			$processedTables[] = $tableName;
			$uidList = array(0);
			foreach ($records as $record) {
				$notificationContext->emitEvent('proceedOperation');

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
					$notificationContext->getLogger()->notice($errorMessage);
				}
			}
			// Searching lost indexes for this table:
			$where = 'tablename=' . $persitenceContext->getDatabaseConnection()->fullQuoteStr($tableName, 'sys_refindex') . ' AND recuid NOT IN (' . implode(',', $uidList) . ')';
			$lostIndexes = $persitenceContext->getDatabaseConnection()->exec_SELECTgetRows('hash', 'sys_refindex', $where);
			if (count($lostIndexes)) {
				$errorMessage = 'Table ' . $tableName . ' has ' . count($lostIndexes);
				if ($dryRun) {
					$errorMessage .= ' which need to be deleted.';
				} else {
					$errorMessage .= ' which have been deleted.';
				}
				$errorCount++;
				$notificationContext->getLogger()->notice($errorMessage);
				if (!$dryRun) {
					$persitenceContext->getDatabaseConnection()->exec_DELETEquery('sys_refindex', $where);
				}
			}
		}

		// Searching lost indexes for non-existing tables:
		$where = 'tablename NOT IN (' . implode(',', $persitenceContext->getDatabaseConnection()->fullQuoteArray($processedTables, 'sys_refindex')) . ')';
		$lostTablesCount = $persitenceContext->getDatabaseConnection()->exec_SELECTcountRows('hash', 'sys_refindex', $where);
		if ($lostTablesCount) {
			$errorMessage = 'Found ' . $lostTablesCount . ' indexes for not existing tables.';
			$errorCount++;
			$notificationContext->getLogger()->notice($errorMessage);
			if (!$dryRun) {
				$persitenceContext->getDatabaseConnection()->exec_DELETEquery('sys_refindex', $where);
				$notificationContext->getLogger()->info('Removed indexes for not existing tables.');
			}
		}

		$notificationContext->emitEvent('endOperation');

		return array($errorCount, $recordCount, $processedTables);
	}

	/**
	 * @param PersistenceContext $persitenceContext
	 * @param array $existingTableNames
	 * @return int
	 */
	protected function countRowsOfAllRegisteredTables(PersistenceContext $persitenceContext, $existingTableNames) {
		$rowsCount = 0;
		foreach (array_keys($persitenceContext->getPersistenceConfiguration()) as $tableName) {
			if (empty($existingTableNames[$tableName])) {
				continue;
			}
			$rowsCount += $persitenceContext->getDatabaseConnection()->exec_SELECTcountRows('uid', $tableName, '1=1');
		}

		return $rowsCount;
	}
}