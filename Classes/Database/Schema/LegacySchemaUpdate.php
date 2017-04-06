<?php
namespace Helhum\Typo3Console\Database\Schema;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Install\Service\SqlExpectedSchemaService;
use TYPO3\CMS\Install\Service\SqlSchemaMigrationService;

/**
 * The database schema update implementation for TYPO3 7.6
 * @deprecated since TYPO3 8.x, will be removed when TYPO3 7.6 support will be removed
 */
class LegacySchemaUpdate implements SchemaUpdateInterface, SingletonInterface
{
    /**
     * @var SqlSchemaMigrationService
     */
    private $schemaMigrationService;

    /**
     * @var SqlExpectedSchemaService
     */
    private $expectedSchemaService;

    /**
     * SchemaUpdate constructor.
     *
     * @param SqlSchemaMigrationService $schemaMigrationService
     * @param SqlExpectedSchemaService $expectedSchemaService
     */
    public function __construct(SqlSchemaMigrationService $schemaMigrationService, SqlExpectedSchemaService $expectedSchemaService)
    {
        $this->schemaMigrationService = $schemaMigrationService;
        $this->expectedSchemaService = $expectedSchemaService;
    }

    /**
     * Get all schema updates that are considered (relatively) safe
     *
     * @return array
     */
    public function getSafeUpdates()
    {
        $expectedSchema = $this->expectedSchemaService->getExpectedDatabaseSchema();
        $currentSchema = $this->schemaMigrationService->getFieldDefinitions_database();
        $addCreateChange = $this->schemaMigrationService->getDatabaseExtra($expectedSchema, $currentSchema);

        return $this->schemaMigrationService->getUpdateSuggestions($addCreateChange);
    }

    /**
     * Get all schema updates that are destructive (renaming/ deleting fields/ tables)
     *
     * @return array
     */
    public function getDestructiveUpdates()
    {
        $expectedSchema = $this->expectedSchemaService->getExpectedDatabaseSchema();
        $currentSchema = $this->schemaMigrationService->getFieldDefinitions_database();
        $dropRename = $this->schemaMigrationService->getDatabaseExtra($currentSchema, $expectedSchema);

        return $this->schemaMigrationService->getUpdateSuggestions($dropRename, 'remove');
    }

    /**
     * Actually execute the migration to the new schema
     *
     * @param array $statements
     * @param array $selectedStatements
     * @return array
     */
    public function migrate(array $statements, array $selectedStatements)
    {
        $result = $this->schemaMigrationService->performUpdateQueries($statements, $selectedStatements);
        if ($result === true) {
            return [];
        }
        return $result;
    }
}
