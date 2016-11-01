<?php
namespace Helhum\Typo3Console\Database\Schema;

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

use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\SqlExpectedSchemaService;
use TYPO3\CMS\Install\Service\SqlSchemaMigrationService;

/**
 * List of database schema update types
 */
class SchemaUpdate implements SingletonInterface
{
    public function getSafeUpdates()
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);

        $addCreateChange = $schemaMigrationService->getUpdateSuggestions($sqlStatements);
        // Aggregate the per-connection statements into one flat array
        return array_merge_recursive(...array_values($addCreateChange));
    }

    public function getDestructiveUpdates()
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        // Difference from current to expected
        $dropRename = $schemaMigrationService->getUpdateSuggestions($sqlStatements, true);
        // Aggregate the per-connection statements into one flat array
        return array_merge_recursive(...array_values($dropRename));
    }

    /**
     * @param array $statements
     * @param array $selectedStatements
     * @return array
     */
    public function migrate(array $statements, array $selectedStatements)
    {
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);
        return $schemaMigrator->migrate($statements, $selectedStatements);
    }
}
