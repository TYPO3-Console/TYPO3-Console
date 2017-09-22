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
        $output = $this->commandDispatcher->executeCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);
        $this->assertContains('"field.add", "field.change", "table.add", "table.change"', $output);
    }

    /**
     * @test
     */
    public function allUpdatesCanBePerformedWhenSpecified()
    {
        $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*', '--verbose' => true]);
        $this->assertContains('No schema updates were performed for update types:', $output);
        $this->assertContains('"field.add", "field.change", "field.prefix", "field.drop", "table.add", "table.change", "table.prefix", "table.drop"', $output);
    }

    /**
     * @test
     */
    public function addingAndRemovingFieldsAndTablesIncludingVerbositySwitchWork()
    {
        $this->installFixtureExtensionCode('ext_test');
        $this->commandDispatcher->executeCommand('install:generatepackagestates', ['--activate-default' => true]);

        $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*', '--verbose' => true]);

        $this->assertContains('The following database schema updates were performed:', $output);
        $this->assertContains('Change fields', $output);
        $this->assertContains('Add tables', $output);

        $this->removeFixtureExtensionCode('ext_test');
        $this->commandDispatcher->executeCommand('install:generatepackagestates', ['--activate-default' => true]);

        $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*', '--verbose' => true]);

        $this->assertContains('The following database schema updates were performed:', $output);
        $this->assertContains('SQL Statements ', $output);
        $this->assertContains('Change fields', $output);
        $this->assertContains('Prefix tables', $output);

        $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*']);

        $this->assertContains('The following database schema updates were performed:', $output);
        $this->assertNotContains('SQL Statements ', $output);
        $this->assertContains('Drop tables', $output);

        $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*', '--verbose' => true]);

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
        $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*']);
        $this->assertContains('The following database schema updates were performed:', $output);
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function schemaUpdateShowsErrorMessageIfTheyOccur()
    {
        $this->installFixtureExtensionCode('ext_broken_sql');
        $this->commandDispatcher->executeCommand('install:generatepackagestates', ['--activate-default' => true]);
        try {
            $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*']);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertContains('The following errors occurred:', $output);
        $this->assertNotContains('SQL Statement', $output);
        try {
            $output = $this->commandDispatcher->executeCommand('database:updateschema', ['--schema-update-types' => '*', '--verbose' => true]);
        } catch (FailedSubProcessCommandException $e) {
            $output = $e->getOutputMessage();
        }
        $this->assertContains('The following errors occurred:', $output);
        $this->assertContains('SQL Statement', $output);
        $this->removeFixtureExtensionCode('ext_broken_sql');
        $this->commandDispatcher->executeCommand('install:generatepackagestates', ['--activate-default' => true]);
    }

    /**
     * @test
     */
    public function databaseCanBeExportedAndImported()
    {
        $this->backupDatabase();
        $output = $this->commandDispatcher->executeCommand('database:export');
        $this->executeMysqlQuery('DROP DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $this->executeMysqlQuery('CREATE DATABASE ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        $this->commandDispatcher->executeCommand('database:import', [], [], $output);
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function databaseExportCanExcludeTables()
    {
        $output = $this->commandDispatcher->executeCommand('database:export', ['--exclude-tables' => 'sys_log']);
        $this->assertNotContains('CREATE TABLE `sys_log`', $output);
    }
}
