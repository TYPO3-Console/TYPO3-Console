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
        $this->assertContains('No schema updates were performed for update types:', $output);
        $this->assertContains('"field.add", "field.change", "table.add", "table.change"', $output);
    }

    /**
     * @test
     */
    public function multipleUpdateTypesCanBeSpecified()
    {
        $output = $this->executeConsoleCommand('database:updateschema', ['field.add,table.add', '--verbose']);
        $this->assertContains('No schema updates were performed for update types:', $output);
        $this->assertContains('"field.add", "table.add"', $output);
    }

    /**
     * @test
     */
    public function allUpdatesCanBePerformedWhenSpecified()
    {
        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);
        $this->assertContains('No schema updates were performed for update types:', $output);
        $this->assertContains('"field.add", "field.change", "field.prefix", "field.drop", "table.add", "table.change", "table.prefix", "table.drop"', $output);
    }

    /**
     * @test
     */
    public function addingAndRemovingFieldsAndTablesIncludingVerbositySwitchWork()
    {
        self::installFixtureExtensionCode('ext_test');
        try {
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);

            $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

            $this->assertContains('The following database schema updates were performed:', $output);
            $this->assertContains('Change fields', $output);
            $this->assertContains('Add tables', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_test');
        }
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);

        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

        $this->assertContains('The following database schema updates were performed:', $output);
        $this->assertContains('SQL Statements ', $output);
        $this->assertContains('Change fields', $output);
        $this->assertContains('Prefix tables', $output);

        $output = $this->executeConsoleCommand('database:updateschema', ['*']);

        $this->assertContains('The following database schema updates were performed:', $output);
        $this->assertNotContains('SQL Statements ', $output);
        $this->assertContains('Drop tables', $output);

        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

        $this->assertContains('No schema updates were performed for update types:', $output);
    }

    /**
     * @test
     */
    public function databaseSchemaCanBeUpdatedWithExtensionsAccessingDatabaseCaches()
    {
        self::installFixtureExtensionCode('ext_test_cache');
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
        $this->executeMysqlQuery('DROP TABLE IF EXISTS `cache_rootline`');
        try {
            $output = $this->executeConsoleCommand('database:updateschema', ['--verbose']);
            $this->assertContains('CREATE TABLE `cache_rootline`', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_test_cache');
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
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
        $this->assertContains('The following database schema updates were performed:', $output);
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
        $this->assertContains('The following database schema updates were performed:', $output);
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function schemaUpdateShowsErrorMessageIfTheyOccur()
    {
        self::installFixtureExtensionCode('ext_broken_sql');
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
        try {
            $output = $this->commandDispatcher->executeCommand('database:updateschema', ['*']);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertContains('The following errors occurred:', $output);
        $this->assertNotContains('SQL Statement', $output);
        try {
            $output = $this->commandDispatcher->executeCommand('database:updateschema', ['*', '--verbose']);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertContains('The following errors occurred:', $output);
        $this->assertContains('SQL Statement', $output);
        self::removeFixtureExtensionCode('ext_broken_sql');
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
    }

    /**
     * @test
     */
    public function sqlCanBeImported()
    {
        $sql = 'SELECT username from be_users where username="_cli_";';
        $output = $this->executeConsoleCommand('database:import', [], [], $sql);
        $this->assertSame('_cli_', trim($output));
    }

    /**
     * @test
     */
    public function sqlCanBeImportedWithSpecifiedConnection()
    {
        $sql = 'SELECT username from be_users where username="_cli_";';
        $output = $this->executeConsoleCommand('database:import', ['--connection', 'Default'], [], $sql);
        $this->assertSame('_cli_', trim($output));
    }

    /**
     * @test
     */
    public function databaseImportFailsWithNotExistingConnection()
    {
        $sql = 'SELECT username from be_users where username="_cli_";';
        try {
            $output = $this->commandDispatcher->executeCommand('database:import', ['--connection', 'foo'], [], $sql);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertContains('No suitable MySQL connection found for import', $output);
    }

    /**
     * @test
     */
    public function databaseExportFailsWithNotExistingConnection()
    {
        try {
            $output = $this->commandDispatcher->executeCommand('database:export', ['--connection', 'foo']);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertContains('No MySQL connections found to export. Given connection "foo" is not configured as MySQL connection', $output);
    }

    /**
     * @test
     */
    public function databaseExportWorksWithGivenConnection()
    {
        $output = $this->executeConsoleCommand('database:export', ['--connection', 'Default']);
        $this->assertContains('-- Dump of TYPO3 Connection "Default"', $output);
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTables()
    {
        $output = $this->executeConsoleCommand('database:export', ['--exclude', 'sys_log']);
        $this->assertNotContains('CREATE TABLE `sys_log`', $output);
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTablesWithWildcards()
    {
        $output = $this->executeConsoleCommand('database:export', ['--exclude', 'cf_*', '-e', 'cache_*']);
        $this->assertNotContains('CREATE TABLE `cf_', $output);
        $this->assertNotContains('CREATE TABLE `cache_', $output);
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTablesWithDeprecatedOption()
    {
        $output = $this->executeConsoleCommand('database:export', ['--exclude-tables', 'sys_log']);
        $this->assertNotContains('CREATE TABLE `sys_log`', $output);
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTablesWithWildcardsWithDeprecatedOption()
    {
        $output = $this->executeConsoleCommand('database:export', ['--exclude-tables', 'cf_*,cache_*']);
        $this->assertNotContains('CREATE TABLE `cf_', $output);
        $this->assertNotContains('CREATE TABLE `cache_', $output);
    }
}
