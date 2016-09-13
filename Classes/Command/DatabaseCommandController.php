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
use Helhum\Typo3Console\Service\Database\ImportService;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * - field.drop
     * - table.add
     * - table.change
     * - table.drop
     * - table.clear
     *
     * The list of schema update types supports wildcards to specify multiple types, e.g.:
     *
     * - "*" (all updates)
     * - "field.*" (all field updates)
     * - "*.add,*.change" (all add/change updates)
     *
     * To avoid shell matching all types with wildcards should be quoted.
     *
     * <b>Example:</b> <code>./typo3cms database:updateschema "*.add,*.change"</code>
     *
     * @param array $schemaUpdateTypes List of schema update types
     * @param bool $verbose If set, database queries performed are shown in output
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function updateSchemaCommand(array $schemaUpdateTypes, $verbose = false)
    {
        try {
            $schemaUpdateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (\UnexpectedValueException $e) {
            $this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
            $this->sendAndExit(1);
        }

        $result = $this->schemaService->updateSchema($schemaUpdateTypes);

        if ($result->hasPerformedUpdates()) {
            $this->output->outputLine('<info>The following schema updates were performed:</info>');
            $this->schemaUpdateResultRenderer->render($result, $this->output, $verbose);
        } else {
            $this->output->outputLine('No schema updates matching the given types were performed');
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
     * <b>Example (import):</b> <code>ssh remote.server '/path/to/typo3cms database:export' | ./typo3cms database:import</code>
     * <b>Example (select):</b> <code>echo 'SELECT username from be_users WHERE admin=1;' | ./typo3cms database:import</code>
     * <b>Example (interactive):</b> <code>./typo3cms database:import --interactive</code>
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
            array('--skip-column-names'),
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
     * <warning>This command passes the plain text database password to the command line process.</warning>
     * This means, that users that have the permission to observe running processes,
     * will be able to read your password.
     * If this imposes a security risk for you, then refrain from using this command!
     */
    public function exportCommand()
    {
        $mysqlCommand = new MysqlCommand(
            $this->connectionConfiguration->build(),
            new ProcessBuilder()
        );
        $exitCode = $mysqlCommand->mysqldump(
            array(),
            $this->buildOutputClosure()
        );
        $this->quit($exitCode);
    }

    /**
     * Import static content from extension file "ext_tables_static+adt.sql" to database
     *
     * @return void
     */
    public function importStaticDataCommand()
    {
        /** @var ImportService $importService */
        $importService = $this->objectManager->get(ImportService::class);
        $importService->setOutput($this->output);
        $extensionKeys = ExtensionManagementUtility::getLoadedExtensionListArray();
        foreach ($extensionKeys as $extensionKey) {
            try {
                $importService->importStaticSql($extensionKey);
            } catch (\Helhum\Typo3Console\Service\Database\Exception $exception) {
                $this->output->outputFormatted(
                    '<error>Mysql error during static data import: "[%s] %s"</error>',
                    [$exception->getCode(), $exception->getMessage()]
                );
                $this->sendAndExit(1);
            }
        }
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
