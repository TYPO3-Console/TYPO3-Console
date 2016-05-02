<?php
namespace Helhum\Typo3Console\Service\Database;

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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateResult;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Service for database schema migrations
 */
class SchemaService implements SingletonInterface
{
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
    public function updateSchema(array $schemaUpdateTypes)
    {
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
                        // Generate a map of statements as keys and true as values
                        array_combine(array_keys($statements), array_fill(0, count($statements), true))
                    );

                    if ($result === true) {
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
    protected function getStatementTypes(SchemaUpdateType $schemaUpdateType)
    {
        return $this->schemaUpdateTypesStatementTypesMapping[(string)$schemaUpdateType];
    }
}
