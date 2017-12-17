<?php
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
        $this->installFixtureExtensionCode('ext_test');
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);

        $output = $this->executeConsoleCommand('database:updateschema', ['*', '--verbose']);

        $this->assertContains('The following database schema updates were performed:', $output);
        $this->assertContains('Change fields', $output);
        $this->assertContains('Add tables', $output);

        $this->removeFixtureExtensionCode('ext_test');
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
    public function schemaUpdateShowsErrorMessageIfTheyOccur()
    {
        $this->installFixtureExtensionCode('ext_broken_sql');
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
        $this->removeFixtureExtensionCode('ext_broken_sql');
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
    }

    /**
     * @test
     */
    public function databaseCanBeExportedAndImported()
    {
        $this->markTestSkipped('TODO: find out why the input is not correctly passed to stdin');
        $this->backupDatabase();
        $sqlDump = $this->executeConsoleCommand('database:export');
        $this->executeMysqlQuery('DROP DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $this->executeMysqlQuery('CREATE DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $this->executeConsoleCommand('database:import', [], [], $sqlDump);

        $queryResult = $this->executeMysqlQuery('SELECT uid FROM be_users WHERE username="_cli_"');
        $this->assertSame('1', trim($queryResult));

        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTables()
    {
        $output = $this->executeConsoleCommand('database:export', ['--exclude-tables' => 'sys_log']);
        $this->assertNotContains('CREATE TABLE `sys_log`', $output);
    }
}
