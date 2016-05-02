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
     * Renders a table for a schema update result
     *
     * @param SchemaUpdateResult $result Result of the schema update
     * @param ConsoleOutput $output
     */
    public function render(SchemaUpdateResult $result, ConsoleOutput $output)
    {
        $tableRows = array();

        foreach ($result->getPerformedUpdates() as $type => $numberOfUpdates) {
            $tableRows[] = array($this->schemaUpdateTypeLabels[(string)$type], $numberOfUpdates);
        }

        $output->outputTable($tableRows, array('Type', 'Updates'));

        if ($result->hasErrors()) {
            foreach ($result->getErrors() as $type => $errors) {
                $output->outputLine(sprintf('<error>Errors during "%s" schema update:</error>', $this->schemaUpdateTypeLabels[(string)$type]));

                foreach ($errors as $error) {
                    $output->outputFormatted('<error>' . $error . '</error>', array(), 2);
                }
            }
        }
    }
}
