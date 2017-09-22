<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Database\Process\MysqlCommand;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

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
     * Valid schema update types are:
     *
     * - field.add
     * - field.change
     * - field.prefix
     * - field.drop
     * - table.add
     * - table.change
     * - table.prefix
     * - table.drop
     * - safe (includes all necessary operations, to add or change fields or tables)
     * - destructive (includes all operations which rename or drop fields or tables)
     *
     * The list of schema update types supports wildcards to specify multiple types, e.g.:
     *
     * - "*" (all updates)
     * - "field.*" (all field updates)
     * - "*.add,*.change" (all add/change updates)
     *
     * To avoid shell matching all types with wildcards should be quoted.
     *
     * <b>Example:</b> <code>typo3cms database:updateschema "*.add,*.change"</code>
     *
     * @param array $schemaUpdateTypes List of schema update types (default: "safe")
     * @param bool $verbose If set, database queries performed are shown in output
     * @param bool $dryRun If set the updates are only collected and shown, but not executed
     */
    public function updateSchemaCommand(array $schemaUpdateTypes = ['safe'], $verbose = false, $dryRun = false)
    {
        try {
            $expandedSchemaUpdateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (InvalidEnumerationValueException $e) {
            $this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
            $this->sendAndExit(1);
        }

        $result = $this->schemaService->updateSchema($expandedSchemaUpdateTypes, $dryRun);

        if ($result->hasPerformedUpdates()) {
            $this->output->outputLine('<info>The following database schema updates %s performed:</info>', [$dryRun ? 'should be' : 'were']);
            $this->schemaUpdateResultRenderer->render($result, $this->output, $verbose);
        } else {
            $this->output->outputLine(
                '<info>No schema updates %s performed for update type%s:%s</info>',
                [
                    $dryRun ? 'must be' : 'were',
                    count($expandedSchemaUpdateTypes) > 1 ? 's' : '',
                    PHP_EOL . '"' . implode('", "', $expandedSchemaUpdateTypes) . '"',
                ]
            );
        }
        if ($result->hasErrors()) {
            $this->outputLine();
            $this->output->outputLine('<error>The following errors occurred:</error>');
            $this->schemaUpdateResultRenderer->renderErrors($result, $this->output, $verbose);
            $this->quit(1);
        }
    }

    /**
     * Import mysql from stdin
     *
     * This means that this can not only be used to pass insert statements,
     * it but works as well to pass SELECT statements to it.
     * The mysql binary must be available in the path for this command to work.
     * This obviously only works when MySQL is used as DBMS.
     *
     * <b>Example (import):</b> <code>ssh remote.server '/path/to/typo3cms database:export' | typo3cms database:import</code>
     * <b>Example (select):</b> <code>echo 'SELECT username from be_users WHERE admin=1;' | typo3cms database:import</code>
     * <b>Example (interactive):</b> <code>typo3cms database:import --interactive</code>
     *
     * <warning>This command passes the plain text database password to the command line process.</warning>
     * This means, that users that have the permission to observe running processes,
     * will be able to read your password.
     * If this imposes a security risk for you, then refrain from using this command!
     *
     * @param bool $interactive Open an interactive mysql shell using the TYPO3 connection settings.
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function importCommand($interactive = false)
    {
        $mysqlCommand = new MysqlCommand(
            $this->connectionConfiguration->build(),
            new ProcessBuilder()
        );
        $exitCode = $mysqlCommand->mysql(
            $interactive ? [] : ['--skip-column-names'],
            STDIN,
            $this->buildOutputClosure(),
            $interactive
        );
        $this->quit($exitCode);
    }

    /**
     * Export database to stdout
     *
     * Export the database (all tables) directly to stdout.
     * The mysqldump binary must be available in the path for this command to work.
     * This obviously only works when MySQL is used as DBMS.
     *
     * A comma-separated list of tables can be passed to exclude from the export:
     *
     * <b>Example:</b> <code>typo3cms database:export --exclude-tables be_sessions,fe_sessions,sys_log</code>
     *
     * <warning>This command passes the plain text database password to the command line process.</warning>
     * This means, that users that have the permission to observe running processes,
     * will be able to read your password.
     * If this imposes a security risk for you, then refrain from using this command!
     *
     * @param array $excludeTables Comma-separated list of table names to exclude from the export
     */
    public function exportCommand(array $excludeTables = [])
    {
        $dbConfig = $this->connectionConfiguration->build();
        $additionalArguments = [];

        foreach ($excludeTables as $table) {
            $additionalArguments[] = sprintf('--ignore-table=%s.%s', $dbConfig['dbname'], $table);
        }

        $mysqlCommand = new MysqlCommand(
            $dbConfig,
            new ProcessBuilder()
        );
        $exitCode = $mysqlCommand->mysqldump(
            $additionalArguments,
            $this->buildOutputClosure()
        );

        $this->quit($exitCode);
    }

    /**
     * @return \Closure
     */
    protected function buildOutputClosure()
    {
        return function ($type, $data) {
            $output = $this->output->getSymfonyConsoleOutput();
            if (Process::OUT === $type) {
                echo $data;
            } elseif (Process::ERR === $type) {
                $output->getErrorOutput()->write($data);
            }
        };
    }
}
