<?php
declare(strict_types=1);
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

use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The database schema update implementation for TYPO3 8.x
 */
class SchemaUpdate implements SchemaUpdateInterface, SingletonInterface
{
    /**
     * @var SqlReader
     */
    private $sqlReader;

    /**
     * @var SchemaMigrator
     */
    private $schemaMigrator;

    /**
     * SchemaUpdate constructor.
     *
     * @param SqlReader $sqlReader
     * @param SchemaMigrator $schemaMigrator
     */
    public function __construct(SqlReader $sqlReader = null, SchemaMigrator $schemaMigrator = null)
    {
        $this->sqlReader = $sqlReader ?: GeneralUtility::makeInstance(SqlReader::class);
        $this->schemaMigrator = $schemaMigrator ?: GeneralUtility::makeInstance(SchemaMigrator::class);
    }

    /**
     * Get all schema updates that are considered (relatively) safe
     *
     * @return array
     */
    public function getSafeUpdates()
    {
        $sqlStatements = $this->sqlReader->getCreateTableStatementArray($this->sqlReader->getTablesDefinitionString());
        $addCreateChange = $this->schemaMigrator->getUpdateSuggestions($sqlStatements);

        // Aggregate the per-connection statements into one flat array
        return array_merge_recursive(...array_values($addCreateChange));
    }

    /**
     * Get all schema updates that are destructive (renaming/ deleting fields/ tables)
     *
     * @return array
     */
    public function getDestructiveUpdates()
    {
        $sqlStatements = $this->sqlReader->getCreateTableStatementArray($this->sqlReader->getTablesDefinitionString());
        // Difference from current to expected
        $dropRename = $this->schemaMigrator->getUpdateSuggestions($sqlStatements, true);

        // Aggregate the per-connection statements into one flat array
        return array_merge_recursive(...array_values($dropRename));
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
        return $this->schemaMigrator->migrate($this->sqlReader->getCreateTableStatementArray($this->sqlReader->getTablesDefinitionString()), $selectedStatements);
    }
}
