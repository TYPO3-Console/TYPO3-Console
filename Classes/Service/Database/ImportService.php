<?php
namespace Helhum\Typo3Console\Service\Database;

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
use Helhum\Typo3Console\Service\Md5FilesHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for database schema migrations
 */
class ImportService implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
     * @inject
     */
    protected $schemaMigrationService;

    /**
     * @var Md5FilesHandler
     */
    protected $md5FilesHandler;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * Set console output class
     *
     * @param ConsoleOutput $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Import static SQL data (normally used for ext_tables_static+adt.sql)
     *
     * @param string $extensionKey Import static data for extension
     * @throws \Helhum\Typo3Console\Service\Database\Exception
     * @see \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::importStaticSql
     */
    public function importStaticSql($extensionKey)
    {
        try {
            $extTablesStaticSqlFile = ExtensionManagementUtility::extPath($extensionKey) .
                'ext_tables_static+adt.sql';

            if (
                !file_exists($extTablesStaticSqlFile) ||
                !is_readable($extTablesStaticSqlFile) ||
                !$this->isTableUpdateNeeded($extensionKey)
            ) {
                return;
            }

            $this->outputFormatted('Importing static sql data for extension "%s"', [$extensionKey]);

            $rawDefinitions = GeneralUtility::getUrl($extTablesStaticSqlFile);
            $statements = $this->schemaMigrationService->getStatementArray($rawDefinitions, true);
            list($statementsPerTable, $insertCount) = $this->schemaMigrationService->getCreateTables($statements, true);
            // Traverse the tables
            foreach ($statementsPerTable as $table => $query) {

                $this->executeAdminQuery('DROP TABLE IF EXISTS ' . $table);
                $this->executeAdminQuery($query);
                if ($insertCount[$table]) {
                    $insertStatements = $this->schemaMigrationService->getTableInsertStatements($statements, $table);
                    foreach ($insertStatements as $statement) {
                        $this->executeAdminQuery($statement);
                    }
                }
            }
            $this->getMd5FilesHandler()->update($extensionKey, md5($rawDefinitions));
        } catch (\Helhum\Typo3Console\Service\Database\Exception $exception) {
            // Remove md5 file at error
            $this->getMd5FilesHandler()->removeFile($extensionKey);
            throw $exception;
        }
    }

    /**
     * Wrapper for \TYPO3\CMS\Core\Database\DatabaseConnection::admin_query
     *
     * This method is originally derived from \TYPO3\CMS\Core\Database\DatabaseConnection::admin_query
     * with the only difference, that query statements are put out to the console window before execution.
     *
     * @param string $query Query to execute
     * @return bool|\mysqli_result|object
     * @throws \Helhum\Typo3Console\Service\Database\Exception
     */
    protected function executeAdminQuery($query)
    {
        $result = $this->getDatabaseConnection()->admin_query($query);
        $this->outputFormatted($query, [], 2);
        if ($this->getDatabaseConnection()->sql_error()) {
            throw new \Helhum\Typo3Console\Service\Database\Exception(
                $this->getDatabaseConnection()->sql_error(),
                $this->getDatabaseConnection()->sql_errno()
            );
        }
        return $result;
    }

    /**
     * Formats the given text to fit into the maximum line length and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param int $leftPadding The number of spaces to use for indentation
     * @return void
     * @see outputLine()
     */
    protected function outputFormatted($text = '', array $arguments = array(), $leftPadding = 0)
    {
        if ($this->output instanceof ConsoleOutput) {
            $this->output->outputFormatted($text, $arguments, $leftPadding);
        }
    }

    /**
     * Check if table update is needed using md5 checksum for the content of "ext_tables_static+adt.sql" file.
     *
     * @param string $extension Extension name
     * @return bool
     */
    protected function isTableUpdateNeeded($extension)
    {
        $md5 = md5_file(ExtensionManagementUtility::extPath($extension) . 'ext_tables_static+adt.sql');
        return ($md5 !== $this->getMd5FilesHandler()->getMd5($extension));
    }

    /**
     * Get database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }


    /**
     * Get md5 files handler service class
     *
     * @return Md5FilesHandler
     */
    protected function getMd5FilesHandler()
    {
        if ($this->md5FilesHandler === null) {
            $this->md5FilesHandler = GeneralUtility::makeInstance(Md5FilesHandler::class, 'staticImport');
        }
        return $this->md5FilesHandler;
    }
}
