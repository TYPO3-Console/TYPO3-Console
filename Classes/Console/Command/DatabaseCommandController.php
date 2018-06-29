<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Annotation\Command\Definition;
use Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration;
use Helhum\Typo3Console\Database\Process\MysqlCommand;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateResultRenderer;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Database\Schema\TableMatcher;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\Database\SchemaService;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database command controller
 */
class DatabaseCommandController extends CommandController
{
    /**
     * @var SchemaService
     */
    private $schemaService;

    /**
     * @var SchemaUpdateResultRenderer
     */
    private $schemaUpdateResultRenderer;

    /**
     * @var ConnectionConfiguration
     */
    private $connectionConfiguration;

    public function __construct(SchemaService $schemaService, SchemaUpdateResultRenderer $schemaUpdateResultRenderer, ConnectionConfiguration $connectionConfiguration)
    {
        $this->schemaService = $schemaService;
        $this->schemaUpdateResultRenderer = $schemaUpdateResultRenderer;
        $this->connectionConfiguration = $connectionConfiguration;
    }

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
     * <b>Example:</b> <code>%command.full_name% "*.add,*.change"</code>
     *
     * @param array $schemaUpdateTypes List of schema update types (default: "safe")
     * @param bool $dryRun If set the updates are only collected and shown, but not executed
     * @Definition\Argument(name="schemaUpdateTypes")
     */
    public function updateSchemaCommand(array $schemaUpdateTypes = ['safe'], $dryRun = false)
    {
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
        try {
            $expandedSchemaUpdateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (InvalidEnumerationValueException $e) {
            $this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
            $this->quit(1);
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
     * Import mysql queries from stdin
     *
     * This means that this can not only be used to pass insert statements,
     * it but works as well to pass SELECT statements to it.
     * The mysql binary must be available in the path for this command to work.
     * This obviously only works when MySQL is used as DBMS.
     *
     * <b>Example (import):</b> <code>ssh remote.server '/path/to/typo3cms database:export' | %command.full_name%</code>
     * <b>Example (select):</b> <code>echo 'SELECT username from be_users WHERE admin=1;' | %command.full_name%</code>
     * <b>Example (interactive):</b> <code>%command.full_name% --interactive</code>
     *
     * @param bool $interactive Open an interactive mysql shell using the TYPO3 connection settings.
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function importCommand($interactive = false)
    {
        $connectionName = 'Default';
        $availableMysqlConnectionNames = $this->connectionConfiguration->getAvailableConnectionNames('mysql');
        if (empty($availableMysqlConnectionNames) || !in_array($connectionName, $availableMysqlConnectionNames, true)) {
            $this->output('<error>No suitable MySQL connection found to import to</error>');
            $this->quit(2);
        }

        $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build($connectionName), [], $this->output->getSymfonyConsoleOutput());
        $exitCode = $mysqlCommand->mysql(
            $interactive ? [] : ['--skip-column-names'],
            STDIN,
            null,
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
     * <b>Example:</b> <code>%command.full_name% --exclude-tables 'cf_*,cache_*,[bf]e_sessions,sys_log'</code>
     *
     * @param array $excludeTables Comma-separated list of table names to exclude from the export. Wildcards are supported.
     */
    public function exportCommand(array $excludeTables = [])
    {
        $availableMysqlConnectionNames = $this->connectionConfiguration->getAvailableConnectionNames('mysql');
        if (empty($availableMysqlConnectionNames)) {
            $this->output('<error>No MySQL connections found to export</error>');
            $this->quit(2);
        }

        foreach ($availableMysqlConnectionNames as $mysqlConnectionName) {
            $dbConfig = $this->connectionConfiguration->build($mysqlConnectionName);
            $additionalArguments = [
                '--opt',
                '--single-transaction',
            ];

            if ($this->output->getSymfonyConsoleOutput()->isVerbose()) {
                $additionalArguments[] = '--verbose';
            }

            foreach ($this->matchTables($excludeTables, $mysqlConnectionName) as $table) {
                $additionalArguments[] = sprintf('--ignore-table=%s.%s', $dbConfig['dbname'], $table);
            }

            $mysqlCommand = new MysqlCommand($dbConfig, [], $this->output->getSymfonyConsoleOutput());
            $exitCode = $mysqlCommand->mysqldump(
                $additionalArguments,
                null,
                $mysqlConnectionName
            );

            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf('Could not dump SQL for connection "%s"', $mysqlConnectionName), $exitCode);
            }
        }
    }

    private function matchTables(array $excludeTables, string $connectionName): array
    {
        if (empty($excludeTables)) {
            return [];
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName($connectionName);

        return (new TableMatcher())->match($connection, ...$excludeTables);
    }
}
