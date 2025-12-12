<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Functional\Command;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;

class DatabaseCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function safeUpdatesCanBePerformedWithNoFurtherArguments()
    {
        $output = $this->executeConsoleCommand('database:updateschema');
        $this->assertStringContainsString('No schema updates were performed for update types:', $output);
        $this->assertStringContainsString('"field.add", "field.change", "table.add", "table.change"', $output);
    }

    /**
     * @test
     */
    public function multipleUpdateTypesCanBeSpecified()
    {
        $output = $this->executeConsoleCommand('database:updateschema', ['field.add,table.add', '--verbose']);
        $this->assertStringContainsString('No schema updates were performed for update types:', $output);
        $this->assertStringContainsString('"field.add", "table.add"', $output);
    }

    /**
     * @test
     */
    public function allUpdatesCanBePerformedWhenSpecified()
    {
        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);
        $this->assertStringContainsString('No schema updates were performed for update types:', $output);
        $this->assertStringContainsString('"field.add", "field.change", "field.prefix", "field.drop", "table.add", "table.change", "table.prefix", "table.drop"', $output);
    }

    /**
     * @test
     */
    public function addingAndRemovingFieldsAndTablesIncludingVerbositySwitchWork()
    {
        self::installFixtureExtensionCode('ext_test');
        try {
            $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

            $this->assertStringContainsString('The following database schema updates were performed:', $output);
            $this->assertStringContainsString('Change fields', $output);
            $this->assertStringContainsString('Add tables', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_test');
        }
        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

        $this->assertStringContainsString('The following database schema updates were performed:', $output);
        $this->assertStringContainsString('SQL Statements ', $output);
        $this->assertStringContainsString('Change fields', $output);
        $this->assertStringContainsString('Prefix tables', $output);

        $output = $this->executeConsoleCommand('database:updateschema', ['*']);

        $this->assertStringContainsString('The following database schema updates were performed:', $output);
        $this->assertStringNotContainsString('SQL Statements ', $output);
        $this->assertStringContainsString('Drop tables', $output);

        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

        $this->assertStringContainsString('No schema updates were performed for update types:', $output);
    }

    /**
     * @test
     */
    public function databaseSchemaCanBeUpdatedWithExtensionsAccessingDatabaseCaches()
    {
        self::installFixtureExtensionCode('ext_test_cache');
        $this->executeMysqlQuery('DROP TABLE IF EXISTS `cache_rootline`');
        try {
            $output = $this->executeConsoleCommand('database:updateschema', ['--verbose']);
            $this->assertStringContainsString('CREATE TABLE', $output);
            $this->assertStringContainsString('cache_rootline', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_test_cache');
        }
    }

    /**
     * @test
     */
    public function schemaUpdateCanBePerformedWithoutAnyTables()
    {
        $this->backupDatabase();
        $this->executeMysqlQuery('DROP DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $this->executeMysqlQuery('CREATE DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $output = $this->executeConsoleCommand('database:updateschema', ['*']);
        $this->assertStringContainsString('The following database schema updates were performed:', $output);
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function schemaUpdateCanBePerformedWithoutAnyTablesAndShortName()
    {
        $this->backupDatabase();
        $this->executeMysqlQuery('DROP DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $this->executeMysqlQuery('CREATE DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $output = $this->executeConsoleCommand('d:u', ['*']);
        $this->assertStringContainsString('The following database schema updates were performed:', $output);
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function schemaUpdateShowsErrorMessageWithStatementsIfTheyOccur()
    {
        $this->skipOnSqlite();
        self::installFixtureExtensionCode('ext_broken_sql');
        try {
            $output = $this->commandDispatcher->executeCommand('database:updateschema', ['*', '--verbose']);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertStringContainsString('The following errors occurred:', $output);
        $this->assertStringContainsString('SQL Statement', $output);
        self::removeFixtureExtensionCode('ext_broken_sql');
    }

    /**
     * @test
     */
    public function sqlCanBeImported()
    {
        $this->skipOnSqlite();
        $sql = 'SELECT username from be_users where username="_cli_";';
        $output = $this->executeConsoleCommand('database:import', [], [], $sql);
        $this->assertSame('_cli_', trim($output));
    }

    /**
     * @test
     */
    public function sqlCanBeImportedWithSpecifiedConnection()
    {
        $this->skipOnSqlite();
        $sql = 'SELECT username from be_users where username="_cli_";';
        $output = $this->executeConsoleCommand('database:import', ['--connection', 'Default'], [], $sql);
        $this->assertSame('_cli_', trim($output));
    }

    /**
     * @test
     */
    public function sqlCanBeImportedWithSpecifiedConnectionAndSpecialSymfonyFormattingInstructions()
    {
        $this->skipOnSqlite();
        $sql = 'SELECT "<error>I shouldn\'t be styled because I\'m user data</error>";';
        $output = $this->executeConsoleCommand('database:import', ['--connection', 'Default'], [], $sql);
        $this->assertSame('<error>I shouldn\'t be styled because I\'m user data</error>', $output);
    }

    /**
     * @test
     */
    public function databaseImportFailsWithNotExistingConnection()
    {
        $this->skipOnSqlite();
        $sql = 'SELECT username from be_users where username="_cli_";';
        try {
            $output = $this->commandDispatcher->executeCommand('database:import', ['--connection', 'foo'], [], $sql);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertStringContainsString('No suitable MySQL connection found for import', $output);
    }

    /**
     * @test
     */
    public function databaseExportFailsWithNotExistingConnection()
    {
        $this->skipOnSqlite();
        try {
            $output = $this->commandDispatcher->executeCommand('database:export', ['--connection', 'foo']);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertStringContainsString('No MySQL connections found to export. Given connection "foo" is not configured as MySQL connection', $output);
    }

    /**
     * @test
     */
    public function databaseExportWorksWithGivenConnection()
    {
        $this->skipOnSqlite();
        $output = $this->executeConsoleCommand('database:export', ['--connection', 'Default']);
        $this->assertStringContainsString('-- Dump of TYPO3 Connection "Default"', $output);
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTables()
    {
        $this->skipOnSqlite();
        $output = $this->executeConsoleCommand('database:export', ['--exclude', 'sys_log']);
        $this->assertStringNotContainsString('CREATE TABLE `sys_log`', $output);
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTablesWithWildcards()
    {
        $this->skipOnSqlite();
        $output = $this->executeConsoleCommand('database:export', ['--exclude', 'cf_*', '-e', 'cache_*']);
        $this->assertStringNotContainsString('CREATE TABLE `cf_', $output);
        $this->assertStringNotContainsString('CREATE TABLE `cache_', $output);
    }
}
