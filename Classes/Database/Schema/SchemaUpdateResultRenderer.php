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
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;

/**
 * List of database schema update types
 */
class SchemaUpdateResultRenderer
{
    /**
     * Mapping of schema update types to human-readable labels
     *
     * @var array
     */
    private static $schemaUpdateTypeLabels = [
        SchemaUpdateType::FIELD_ADD => 'Add fields',
        SchemaUpdateType::FIELD_CHANGE => 'Change fields',
        SchemaUpdateType::FIELD_PREFIX => 'Prefix fields',
        SchemaUpdateType::FIELD_DROP => 'Drop fields',
        SchemaUpdateType::TABLE_ADD => 'Add tables',
        SchemaUpdateType::TABLE_CHANGE => 'Change tables',
        SchemaUpdateType::TABLE_CLEAR => 'Clear tables',
        SchemaUpdateType::TABLE_PREFIX => 'Prefix tables',
        SchemaUpdateType::TABLE_DROP => 'Drop tables',
    ];

    /**
     * Renders a table for a schema update result
     *
     * @param SchemaUpdateResult $result Result of the schema update
     * @param ConsoleOutput $output
     * @param bool $includeStatements
     * @param int $maxStatementLength
     */
    public function render(SchemaUpdateResult $result, ConsoleOutput $output, $includeStatements = false, $maxStatementLength = 60)
    {
        $tableRows = [];

        foreach ($result->getPerformedUpdates() as $type => $performedUpdates) {
            $row = [self::$schemaUpdateTypeLabels[(string)$type], count($performedUpdates)];
            if ($includeStatements) {
                $row = [self::$schemaUpdateTypeLabels[(string)$type], implode(chr(10) . chr(10), $this->getTruncatedQueries($performedUpdates, $maxStatementLength))];
            }
            $tableRows[] = $row;
        }
        $tableHeader = ['Type', 'Updates'];
        if ($includeStatements) {
            $tableHeader = ['Type', 'SQL Statements'];
        }
        $output->outputTable($tableRows, $tableHeader);

        if ($result->hasErrors()) {
            foreach ($result->getErrors() as $type => $errors) {
                $output->outputLine(sprintf('<error>Errors during "%s" schema update:</error>', self::$schemaUpdateTypeLabels[(string)$type]));

                foreach ($errors as $error) {
                    $output->outputFormatted('<error>' . $error . '</error>', [], 2);
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
    protected function getTruncatedQueries(array $queries, $truncateAt)
    {
        foreach ($queries as &$query) {
            $truncatedLines = [];
            foreach (explode(chr(10), $query) as $line) {
                $truncatedLines[] = wordwrap($line, $truncateAt, chr(10), true);
            }
            $query = implode(chr(10), $truncatedLines);
        }
        return $queries;
    }
}
