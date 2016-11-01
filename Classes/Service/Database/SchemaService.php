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
     * Perform necessary database schema migrations
     *
     * @param SchemaUpdateType[] $schemaUpdateTypes List of permitted schema update types
     * @param bool $dryRun If true, the database operations are not performed
     * @return SchemaUpdateResult Result of the schema update
     */
    public function updateSchema(array $schemaUpdateTypes, $dryRun = false)
    {
        $expectedSchema = $this->expectedSchemaService->getExpectedDatabaseSchema();
        $currentSchema = $this->schemaMigrationService->getFieldDefinitions_database();

        $addCreateChange = $this->schemaMigrationService->getDatabaseExtra($expectedSchema, $currentSchema);
        $dropRename = $this->schemaMigrationService->getDatabaseExtra($currentSchema, $expectedSchema);

        $updateStatements = [
            SchemaUpdateType::GROUP_SAFE => $this->schemaMigrationService->getUpdateSuggestions($addCreateChange),
            SchemaUpdateType::GROUP_DESTRUCTIVE => $this->schemaMigrationService->getUpdateSuggestions($dropRename, 'remove'),
        ];

        $updateResult = new SchemaUpdateResult();

        foreach ($schemaUpdateTypes as $schemaUpdateType) {
            foreach ($schemaUpdateType->getStatementTypes() as $statementType => $statementGroup) {
                if (isset($updateStatements[$statementGroup][$statementType])) {
                    $statements = $updateStatements[$statementGroup][$statementType];
                    if ($dryRun) {
                        $updateResult->addPerformedUpdates($schemaUpdateType, $statements);
                    } else {
                        $result = $this->schemaMigrationService->performUpdateQueries(
                            $statements,
                            // Generate a map of statements as keys and true as values
                            array_combine(array_keys($statements), array_fill(0, count($statements), true))
                        );
                        if ($result === true) {
                            $updateResult->addPerformedUpdates($schemaUpdateType, $statements);
                        } else {
                            $updateResult->addErrors($schemaUpdateType, $result);
                        }
                    }
                }
            }
        }

        return $updateResult;
    }
}
