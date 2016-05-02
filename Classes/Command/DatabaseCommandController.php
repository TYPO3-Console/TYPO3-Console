<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\ImportExport\Database\Process\MysqlCommand;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateResult;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateType;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

/**
 * Database command controller
 */
class DatabaseCommandController extends CommandController
{
    /**
     * @var \Helhum\Typo3Console\Service\Database\Schema\SchemaService
     * @inject
     */
    protected $schemaService;

    /**
     * @var \Helhum\Typo3Console\ImportExport\Database\Configuration\ConnectionConfiguration
     * @inject
     */
    protected $connectionConfiguration;

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
     */
    public function updateSchemaCommand(array $schemaUpdateTypes)
    {
        try {
            $schemaUpdateTypes = $this->expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (\UnexpectedValueException $e) {
            $this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
            $this->sendAndExit(1);
        }

        $result = $this->schemaService->updateSchema($schemaUpdateTypes);

        if ($result->hasPerformedUpdates()) {
            $this->output->outputLine('<info>The following schema updates where performed:</info>');
            $this->outputSchemaUpdateResult($result);
        } else {
            $this->output->outputLine('No schema updates matching the given types where performed');
        }
    }

    /**
     * Read mysql from stdin.
     *
     * This means that this can not only be used to pass insert statements,
     * it but works as well to pass SELECT statements to it.
     * The mysql binary must be available in the path for this command to work.
     */
    public function importCommand()
    {
        $mysqlCommand = new MysqlCommand(
            $this->connectionConfiguration->build(),
            new ProcessBuilder()
        );
        $exitCode = $mysqlCommand->mysql(
            array('--skip-column-names'),
            STDIN,
            function ($type, $output) {
                if (Process::OUT === $type) {
                    // Explicitly just echo out for now (avoid Symfony console formatting)
                    echo $output;
                } else {
                    $this->output('<error>' . $output . '</error>');
                }
            }
        );
        $this->quit($exitCode);
    }

    /**
     * Export the database (all tables) directly to stdout
     *
     * The mysqldump binary must be available in the path for this command to work.
     */
    public function exportCommand()
    {
        $mysqlCommand = new MysqlCommand(
            $this->connectionConfiguration->build(),
            new ProcessBuilder()
        );
        $exitCode = $mysqlCommand->mysqldump(
            array(),
            function ($type, $output) {
                if (Process::OUT === $type) {
                    echo $output;
                } else {
                    $this->output('<error>' . $output . '</error>');
                }
            }
        );
        $this->quit($exitCode);
    }

    /**
     * Expands wildcards in schema update types, e.g. field.* or *.change
     *
     * @param array $schemaUpdateTypes List of schema update types
     * @return SchemaUpdateType[]
     * @throws \UnexpectedValueException If an invalid schema update type was passed
     */
    protected function expandSchemaUpdateTypes(array $schemaUpdateTypes)
    {
        $expandedSchemaUpdateTypes = array();
        $schemaUpdateTypeConstants = array_values(SchemaUpdateType::getConstants());

        // Collect total list of types by expanding wildcards
        foreach ($schemaUpdateTypes as $schemaUpdateType) {
            if (strpos($schemaUpdateType, '*') !== false) {
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
     * @return void
     */
    protected function outputSchemaUpdateResult(SchemaUpdateResult $result)
    {
        $tableRows = array();

        foreach ($result->getPerformedUpdates() as $type => $numberOfUpdates) {
            $tableRows[] = array($this->schemaUpdateTypeLabels[(string)$type], $numberOfUpdates);
        }

        $this->output->outputTable($tableRows, array('Type', 'Updates'));

        if ($result->hasErrors()) {
            foreach ($result->getErrors() as $type => $errors) {
                $this->output->outputLine(sprintf('<error>Errors during "%s" schema update:</error>', $this->schemaUpdateTypeLabels[(string)$type]));

                foreach ($errors as $error) {
                    $this->output->outputFormatted('<error>' . $error . '</error>', array(), 2);
                }
            }
        }
    }
}
