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
     * Update database schema (TYPO3 Database Compare)
     *
     * Compares the current database schema with schema definition
     * from extensions's ext_tables.sql files and updates the schema based on the definition.
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
     * @param string $connection TYPO3 database connection name
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function importCommand($interactive = false, string $connection = 'Default')
    {
        $availableConnectionNames = $this->connectionConfiguration->getAvailableConnectionNames('mysql');
        if (empty($availableConnectionNames) || !in_array($connection, $availableConnectionNames, true)) {
            $this->output('<error>No suitable MySQL connection found for import.</error>');
            $this->quit(2);
        }

        $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build($connection), [], $this->output->getSymfonyConsoleOutput());
        $exitCode = $mysqlCommand->mysql(
            $interactive ? [] : ['--skip-column-names'],
            STDIN,
            null,
            $interactive
        );
        $this->quit($exitCode);
    }
}
