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

use Helhum\Typo3Console\Database\Process\MysqlCommand;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Database command controller
 */
class DatabaseCommandController extends CommandController
{
    /**
     * @var \Helhum\Typo3Console\Service\Database\SchemaService
     * @inject
     */
    protected $schemaService;

    /**
     * @var \Helhum\Typo3Console\Database\Schema\SchemaUpdateResultRenderer
     * @inject
     */
    protected $schemaUpdateResultRenderer;

    /**
     * @var \Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration
     * @inject
     */
    protected $connectionConfiguration;

    /**
     * Update database schema
     *
     * See Helhum\Typo3Console\Database\Schema\SchemaUpdateType for a list of valid schema update types.
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
            $schemaUpdateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (\UnexpectedValueException $e) {
            $this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
            $this->sendAndExit(1);
        }

        $result = $this->schemaService->updateSchema($schemaUpdateTypes);

        if ($result->hasPerformedUpdates()) {
            $this->output->outputLine('<info>The following schema updates where performed:</info>');
            $this->schemaUpdateResultRenderer->render($result, $this->output);
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
     *
     * @param bool $interactive
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function importCommand($interactive = false)
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
            },
            $interactive
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
}
