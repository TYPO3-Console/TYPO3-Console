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
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceVersionRecordsMigration;

class UpgradeCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function canCheckExtensionConstraints(): void
    {
        $output = $this->executeConsoleCommand('upgrade:checkextensionconstraints');
        $this->assertContains('All third party extensions claim to be compatible with TYPO3 version', $output);
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsReturnsErrorCodeOnFailure(): void
    {
        self::installFixtureExtensionCode('ext_test');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_test']);
        try {
            $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--typo3-version' => '3.6.0']);
            $this->fail('upgrade:checkextensionconstraints should have failed');
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
            $this->assertContains('"ext_test" requires TYPO3 versions 4.5.0', $e->getOutputMessage());
        } finally {
            self::removeFixtureExtensionCode('ext_test');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function checkExtensionCompatibilityReportsBrokenCodeInExtTables(): void
    {
        self::installFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');

        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', ['ext_broken_ext_tables']);
        $this->assertSame('false', $output);

        self::removeFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');
    }

    /**
     * @test
     */
    public function checkExtensionCompatibilityDoeNotReportBrokenCodeInExtTablesWithConfigOnlyCheck(): void
    {
        self::installFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');

        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', ['ext_broken_ext_tables', '--config-only']);
        $this->assertSame('true', $output);

        self::removeFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsIssuesWarningForInvalidExtensionKeys(): void
    {
        $output = $this->executeConsoleCommand('upgrade:checkextensionconstraints', ['foo,bar']);
        $this->assertContains('Extension "foo" is not found in the system', $output);
        $this->assertContains('Extension "bar" is not found in the system', $output);
    }

    /**
     * @test
     */
    public function upgradePrepareCanBeRun(): void
    {
        $this->executeConsoleCommand('configuration:remove', ['EXTCONF/helhum-typo3-console', '--force']);
        $output = $this->executeConsoleCommand('upgrade:prepare');
        $this->assertNotContains('Preparation has been done before, repeating preparation and checking extensions', $output);
        $this->assertContains('Upgrade preparations successfully executed', $output);
        $output = $this->executeConsoleCommand('upgrade:prepare');
        $this->assertContains('Preparation has been done before, repeating preparation and checking extensions', $output);
        $this->assertContains('Upgrade preparations successfully executed', $output);
    }

    /**
     * @test
     */
    public function upgradeListShowsActiveWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_upgrade']);
        $this->executeConsoleCommand('upgrade:prepare');

        $output = $this->executeConsoleCommand('upgrade:list');
        $this->assertContains('normalWizard', $output);
        $this->assertContains('Just a regular wizard', $output);
        $output = $this->executeConsoleCommand('upgrade:list', ['-v']);
        $this->assertContains('normalWizard', $output);
        $this->assertContains('Fly you fools', $output);
        $this->assertContains('repeatableWizard', $output);
        $this->assertContains('It is not despair', $output);

        self::removeFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
    }

    /**
     * @test
     */
    public function upgradeRunCanRunIndividualWizardWhichIsMarkedExecutedAndCanBeRunForced(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_upgrade']);
        try {
            $this->executeConsoleCommand('upgrade:prepare');

            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard']);
            $this->assertContains('Successfully executed upgrade wizard "normalWizard"', $output);

            $output = $this->executeConsoleCommand('upgrade:list');
            $this->assertNotContains('normalWizard', $output);
            $this->assertContains('repeatableWizard', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard']);
            $this->assertContains('Upgrade wizard "normalWizard" was skipped because it is marked as done', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard', '--force']);
            $this->assertContains('Successfully executed upgrade wizard "normalWizard"', $output);
            $this->assertContains('Upgrade wizard "normalWizard" was executed (forced)', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['repeatableWizard']);
            $this->assertContains('Successfully executed upgrade wizard "repeatableWizard"', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['repeatableWizard']);
            $this->assertContains('Successfully executed upgrade wizard "repeatableWizard"', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function upgradeRunAllRunsAllWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND entry_key NOT LIKE \'%Argon2iPasswordHashes\'');
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--deny', 'all']);
            $this->assertContains('pagesLanguageOverlayBeGroupsAccessRights', $output);
            $this->assertContains('Skipped wizard "typo3DbLegacyExtension"', $output);
            $this->assertContains('Skipped wizard "rsaauthExtension"', $output);
            $this->assertContains('Skipped wizard "confirmableWizard" but it needs confirmation', $output);
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--confirm', 'all']);
            $this->assertContains('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $output = $this->executeConsoleCommand('upgrade:list', [], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertContains('None', $output);
            $output = $this->executeConsoleCommand('upgrade:run', ['all'], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertContains('All wizards done. Nothing to do.', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanRunMultipleSpecifiedWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND entry_key LIKE \'%ext_upgrade%\'');
            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard', 'confirmableWizard', '--confirm', 'all']);
            $this->assertContains('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $this->assertContains('Successfully executed upgrade wizard "normalWizard"', $output);
            $output = $this->executeConsoleCommand('upgrade:list', [], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertContains('None', $output);
            $output = $this->executeConsoleCommand('upgrade:run', ['all'], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertContains('All wizards done. Nothing to do.', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanConfirmAllAndDenySomeConfirmableWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND (entry_key LIKE \'%ext_upgrade%\' OR entry_key LIKE \'%RsaauthExtractionUpdate%\')');
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--confirm', 'all', '--deny', 'rsaauthExtension']);
            $this->assertContains('Skipped wizard "rsaauthExtension"', $output);
            $this->assertContains('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $this->assertContains('Successfully executed upgrade wizard "normalWizard"', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanDenyAllAndConfirmSomeConfirmableWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND (entry_key LIKE \'%ext_upgrade%\' OR entry_key LIKE \'%RsaauthExtractionUpdate%\')');
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--deny', 'all', '--confirm', 'confirmableWizard']);
            $this->assertContains('Skipped wizard "rsaauthExtension"', $output);
            $this->assertContains('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $this->assertContains('Successfully executed upgrade wizard "normalWizard"', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function rowUpdaterCanBeForced(): void
    {
        $output = $this->executeConsoleCommand('upgrade:list');
        $this->assertContains('None', $output);
        $output = $this->executeConsoleCommand('upgrade:list', ['--all']);
        $this->assertContains('Row Updaters:', $output);
        $this->assertContains(WorkspaceVersionRecordsMigration::class, $output);
        $output = $this->executeConsoleCommand('upgrade:run', ['databaseRowsUpdateWizard']);
        $this->assertContains('Upgrade wizard "databaseRowsUpdateWizard" was skipped because no operation is needed', $output);
        $output = $this->executeConsoleCommand('upgrade:run', ['databaseRowsUpdateWizard', '--force-row-updater', WorkspaceVersionRecordsMigration::class]);
        $this->assertContains('Successfully executed upgrade wizard "databaseRowsUpdateWizard"', $output);
    }
}
