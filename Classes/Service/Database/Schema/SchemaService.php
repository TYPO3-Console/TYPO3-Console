<?php
namespace Helhum\Typo3Console\Service\Database\Schema;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Mathias Brodala <mbrodala@pagemachine.de>
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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Service for database schema migrations
 */
class SchemaService implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
	 * @inject
	 */
	protected $schemaMigrationService;

	/**
	 * @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService
	 * @inject
	 */
	protected $expectedSchemaService;

	/**
	 * Mapping of schema update types to internal statement types
	 *
	 * @var array
	 */
	protected $schemaUpdateTypesStatementTypesMapping = array(
		SchemaUpdateType::FIELD_ADD => array('add'),
		SchemaUpdateType::FIELD_CHANGE => array('change'),
		SchemaUpdateType::FIELD_DROP => array('drop'),
		SchemaUpdateType::TABLE_ADD => array('create_table'),
		SchemaUpdateType::TABLE_CHANGE => array('change_table'),
		SchemaUpdateType::TABLE_CLEAR => array('clear_table'),
		SchemaUpdateType::TABLE_DROP => array('drop_table'),
	);

	/**
	 * Perform necessary database schema migrations
	 *
	 * @param SchemaUpdateType[] $schemaUpdateTypes List of permitted schema update types
	 * @return SchemaUpdateResult Result of the schema update
	 */
	public function updateSchema(array $schemaUpdateTypes) {
		$expectedSchema = $this->expectedSchemaService->getExpectedDatabaseSchema();
		$currentSchema = $this->schemaMigrationService->getFieldDefinitions_database();

		$addCreateChange = $this->schemaMigrationService->getDatabaseExtra($expectedSchema, $currentSchema);
		$dropRename = $this->schemaMigrationService->getDatabaseExtra($currentSchema, $expectedSchema);

		$updateStatements = array();
		ArrayUtility::mergeRecursiveWithOverrule($updateStatements, $this->schemaMigrationService->getUpdateSuggestions($addCreateChange));
		ArrayUtility::mergeRecursiveWithOverrule($updateStatements, $this->schemaMigrationService->getUpdateSuggestions($dropRename, 'remove'));

		$updateResult = new SchemaUpdateResult();

		foreach ($schemaUpdateTypes as $schemaUpdateType) {
			$statementTypes = $this->getStatementTypes($schemaUpdateType);

			foreach ($statementTypes as $statementType) {
				if (isset($updateStatements[$statementType])) {
					$statements = $updateStatements[$statementType];
					$result = $this->schemaMigrationService->performUpdateQueries(
						$statements,
						// Generate a map of statements as keys and TRUE as values
						array_combine(array_keys($statements), array_fill(0, count($statements), TRUE))
					);

					if ($result === TRUE) {
						$updateResult->addPerformedUpdates($schemaUpdateType, count($statements));
					} elseif (is_array($result)) {
						$updateResult->addErrors($schemaUpdateType, $result);
					}
				}
			}
		}

		return $updateResult;
	}

	/**
	 * Map schema update type to a list of internal statement types
	 *
	 * @param SchemaUpdateType $schemaUpdateType Schema update types
	 * @return array
	 */
	protected function getStatementTypes(SchemaUpdateType $schemaUpdateType) {
		return $this->schemaUpdateTypesStatementTypesMapping[(string)$schemaUpdateType];
	}
}
