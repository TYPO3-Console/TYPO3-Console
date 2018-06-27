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
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\Database\SchemaService;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

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
     * Import mysql from stdin
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
        $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build());
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
     * <b>Example:</b> <code>%command.full_name% --exclude-tables be_sessions,fe_sessions,sys_log</code>
     *
     * <warning>This command passes the plain text database password to the command line process.</warning>
     * This means, that users that have the permission to observe running processes,
     * will be able to read your password.
     * If this imposes a security risk for you, then refrain from using this command!
     *
     * @param array $excludeTables Comma-separated list of table names to exclude from the export
     * @param bool $excludeVolatile Exclude cache and session tables
     */
    public function exportCommand(array $excludeTables = [], $excludeVolatile = false)
    {
        $dbConfig = $this->connectionConfiguration->build();
        $additionalArguments = [
            '--opt',
            '--single-transaction',
        ];

        if ($this->output->getSymfonyConsoleOutput()->isVerbose()) {
            $additionalArguments[] = '--verbose';
        }

        if ($excludeVolatile) {
            $excludeTables[] = 'fe_sessions';
            $excludeTables[] = 'be_sessions';
            $excludeTables[] = 'cache_md5params';
            $excludeTables[] = 'cache_treelist';
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $name => $configuration) {
                $cacheBackendClass = '\\' . ltrim($configuration['backend'] ?? Typo3DatabaseBackend::class, '\\');
                $cacheFrontendClass = '\\' . ltrim($configuration['frontend'] ?? VariableFrontend::class, '\\');
                if (!is_a($cacheBackendClass, Typo3DatabaseBackend::class, true)) {
                    continue;
                }

                /** @var Typo3DatabaseBackend $cacheBackend */
                $cacheBackend = new $cacheBackendClass('production', $configuration['options'] ?? []);
                $cacheBackend->setCache(new $cacheFrontendClass($name, $cacheBackend));
                $excludeTables[] = $cacheBackend->getCacheTable();
                $excludeTables[] = $cacheBackend->getTagsTable();
            }
        }

        foreach ($excludeTables as $table) {
            $additionalArguments[] = sprintf('--ignore-table=%s.%s', $dbConfig['dbname'], $table);
        }

        $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build());
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
