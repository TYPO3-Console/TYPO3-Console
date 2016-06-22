<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateException;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateResult;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateType;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

/**
 * Database command controller
 */
class DatabaseCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Service\Database\Schema\SchemaService
	 * @inject
	 */
	protected $schemaService;

	/**
	 * Mapping of schema update types to human-readable labels
	 *
	 * @var array
	 */
	protected $schemaUpdateTypeLabels = array(
		SchemaUpdateType::FIELD_ADD => 'Add fields',
		SchemaUpdateType::FIELD_CHANGE => 'Change fields',
		SchemaUpdateType::FIELD_DROP => 'Drop fields',
		SchemaUpdateType::TABLE_ADD => 'Add tables',
		SchemaUpdateType::TABLE_CHANGE => 'Change tables',
		SchemaUpdateType::TABLE_CLEAR => 'Clear tables',
		SchemaUpdateType::TABLE_DROP => 'Drop tables',
	);

	/**
	 * Update database schema
	 *
	 * See Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateType for a list of valid schema update types.
	 *
	 * The list of schema update types supports wildcards to specify multiple types, e.g.:
	 *
	 * "*" (all updates)
	 * "field.*" (all field updates)
	 * "*.add,*.change" (all add/change updates)
	 *
	 * To avoid shell matching all types with wildcards should be quoted.
	 *
	 * @param array $schemaUpdateTypes List of schema update types
	 * @param bool $verbose If set, database queries performed are shown in output
	 */
	public function updateSchemaCommand(array $schemaUpdateTypes, $verbose = FALSE) {
		try {
			$schemaUpdateTypes = $this->expandSchemaUpdateTypes($schemaUpdateTypes);
		} catch (\UnexpectedValueException $e) {
			$this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
			$this->sendAndExit(1);
		}

		$result = $this->schemaService->updateSchema($schemaUpdateTypes);

		if ($result->hasPerformedUpdates()) {
			$this->output->outputLine('<info>The following schema updates where performed:</info>');
			$this->outputSchemaUpdateResult($result, $verbose);
		} else {
			$this->output->outputLine('No schema updates matching the given types where performed');
		}
	}

	/**
	 * Expands wildcards in schema update types, e.g. field.* or *.change
	 *
	 * @param array $schemaUpdateTypes List of schema update types
	 * @return SchemaUpdateType[]
	 * @throws \UnexpectedValueException If an invalid schema update type was passed
	 */
	protected function expandSchemaUpdateTypes(array $schemaUpdateTypes) {
		$expandedSchemaUpdateTypes = array();
		$schemaUpdateTypeConstants = array_values(SchemaUpdateType::getConstants());

		// Collect total list of types by expanding wildcards
		foreach ($schemaUpdateTypes as $schemaUpdateType) {
			if (strpos($schemaUpdateType, '*') !== FALSE) {
				$matchPattern = '/' . str_replace('\\*', '.+', preg_quote($schemaUpdateType, '/')) . '/';
				$matchingSchemaUpdateTypes = preg_grep($matchPattern, $schemaUpdateTypeConstants);
				$expandedSchemaUpdateTypes = array_merge($expandedSchemaUpdateTypes, $matchingSchemaUpdateTypes);
			} else {
				$expandedSchemaUpdateTypes[] = $schemaUpdateType;
			}
		}

		// Cast to enumeration objects to ensure valid values
		foreach ($expandedSchemaUpdateTypes as &$schemaUpdateType) {
			try {
				$schemaUpdateType = SchemaUpdateType::cast($schemaUpdateType);
			} catch (InvalidEnumerationValueException $e) {
				throw new \UnexpectedValueException(sprintf(
					'Invalid schema update type "%s", must be one of: "%s"',
					$schemaUpdateType,
					implode('", "', $schemaUpdateTypeConstants)
				), 1439460396);
			}
		}

		return $expandedSchemaUpdateTypes;
	}

	/**
	 * Renders a table for a schema update result
	 *
	 * @param SchemaUpdateResult $result Result of the schema update
	 * @param bool $includeStatements TRUE to include the performed statements in the output, FALSE otherwise
	 * @param int $maxStatementLength Wrap statements at the given number of characters
	 * @return void
	 */
	protected function outputSchemaUpdateResult(SchemaUpdateResult $result, $includeStatements = FALSE, $maxStatementLength = 60) {
		$tableRows = array();

		foreach ($result->getPerformedUpdates() as $type => $performedUpdates) {
			$row = array($this->schemaUpdateTypeLabels[(string)$type], count($performedUpdates));
			if ($includeStatements) {
				$row = array($this->schemaUpdateTypeLabels[(string)$type], implode(chr(10) . chr(10), $this->getTruncatedQueries($performedUpdates, $maxStatementLength)));
			}
			$tableRows[] = $row;
		}

		$tableHeader = array('Type', 'Updates');

		if ($includeStatements) {
			$tableHeader = array('Type', 'SQL Statements');
		}

		$this->output->outputTable($tableRows, $tableHeader);

		if ($result->hasErrors()) {
			foreach ($result->getErrors() as $type => $errors) {
				$this->output->outputLine(sprintf('<error>Errors during "%s" schema update:</error>', $this->schemaUpdateTypeLabels[(string)$type]));
				foreach ($errors as $error) {
					$this->output->outputFormatted('<error>' . $error . '</error>', array(), 2);
				}
			}
		}
	}

	/**
	 * Truncate (wrap) query strings at a certain number of characters
	 *
	 * @param array $queries
	 * @param int $truncateAt
	 * @return array
	 */
	protected function getTruncatedQueries(array $queries, $truncateAt) {
		foreach ($queries as &$query) {
			$truncatedLines = array();
			foreach (explode(chr(10), $query) as $line) {
				$truncatedLines[] = wordwrap($line, $truncateAt, chr(10), true);
			}
			$query = implode(chr(10), $truncatedLines);
		}
		return $queries;
	}
}
