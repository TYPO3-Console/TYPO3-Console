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

use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceVersionRecordsMigration;

class UpgradeCommandControllerTest extends AbstractCommandTest
{
    protected function setUp(): void
    {
        self::markTestSkipped('Not sure if it is worth trying to provide improved upgrade commands');
    }

    /**
     * @test
     */
    public function upgradeListShowsActiveWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        try {
            $this->executeConsoleCommand('extension:setup', ['-e', 'ext_upgrade']);

            $output = $this->executeConsoleCommand('upgrade:list');
            $this->assertStringContainsString('normalWizard', $output);
            $this->assertStringContainsString('Just a regular wizard', $output);
            $output = $this->executeConsoleCommand('upgrade:list', ['-v']);
            $this->assertStringContainsString('normalWizard', $output);
            $this->assertStringContainsString('Fly you fools', $output);
            $this->assertStringContainsString('repeatableWizard', $output);
            $this->assertStringContainsString('It is not despair', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanRunIndividualWizardWhichIsMarkedExecutedAndCanBeRunForced(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('extension:setup', ['-e', 'ext_upgrade']);
        try {
            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard']);
            $this->assertStringContainsString('Successfully executed upgrade wizard "normalWizard"', $output);

            $output = $this->executeConsoleCommand('upgrade:list');
            $this->assertStringNotContainsString('normalWizard', $output);
            $this->assertStringContainsString('repeatableWizard', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard']);
            $this->assertStringContainsString('Upgrade wizard "normalWizard" was skipped because it is marked as done', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard', '--force']);
            $this->assertStringContainsString('Successfully executed upgrade wizard "normalWizard"', $output);
            $this->assertStringContainsString('Upgrade wizard "normalWizard" was executed (forced)', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['repeatableWizard']);
            $this->assertStringContainsString('Successfully executed upgrade wizard "repeatableWizard"', $output);

            $output = $this->executeConsoleCommand('upgrade:run', ['repeatableWizard']);
            $this->assertStringContainsString('Successfully executed upgrade wizard "repeatableWizard"', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
        }
    }

    /**
     * @test
     */
    public function upgradeRunAllRunsAllWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('extension:setup', ['-e', 'ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND entry_key NOT LIKE \'%Argon2iPasswordHashes\'');
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--deny', 'all']);
            //TODO: FIXME
            //            $this->assertStringContainsString('pagesLanguageOverlayBeGroupsAccessRights', $output);
            //            $this->assertStringContainsString('Skipped wizard "typo3DbLegacyExtension"', $output);
            $this->assertStringContainsString('Skipped wizard "anotherConfirmableUpgradeWizard"', $output);
            $this->assertStringContainsString('Skipped wizard "confirmableWizard" but it needs confirmation', $output);
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--confirm', 'all']);
            $this->assertStringContainsString('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $output = $this->executeConsoleCommand('upgrade:list', [], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertStringContainsString('None', $output);
            $output = $this->executeConsoleCommand('upgrade:run', ['all'], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertStringContainsString('All wizards done. Nothing to do.', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanRunMultipleSpecifiedWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('extension:setup', ['-e', 'ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND entry_key LIKE \'%ext_upgrade%\'');
            $output = $this->executeConsoleCommand('upgrade:run', ['normalWizard', 'confirmableWizard', 'anotherConfirmableUpgradeWizard', '--confirm', 'all']);
            $this->assertStringContainsString('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $this->assertStringContainsString('Successfully executed upgrade wizard "normalWizard"', $output);
            $output = $this->executeConsoleCommand('upgrade:list', [], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertStringContainsString('None', $output);
            $output = $this->executeConsoleCommand('upgrade:run', ['all'], ['TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD' => 1]);
            $this->assertStringContainsString('All wizards done. Nothing to do.', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanConfirmAllAndDenySomeConfirmableWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('extension:setup', ['-e', 'ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND (entry_key LIKE \'%ext_upgrade%\' OR entry_key LIKE \'%RsaauthExtractionUpdate%\')');
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--confirm', 'all', '--deny', 'anotherConfirmableUpgradeWizard']);
            $this->assertStringContainsString('Skipped wizard "anotherConfirmableUpgradeWizard"', $output);
            $this->assertStringContainsString('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $this->assertStringContainsString('Successfully executed upgrade wizard "normalWizard"', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
        }
    }

    /**
     * @test
     */
    public function upgradeRunCanDenyAllAndConfirmSomeConfirmableWizards(): void
    {
        self::installFixtureExtensionCode('ext_upgrade');
        $this->executeConsoleCommand('extension:setup', ['-e', 'ext_upgrade']);
        try {
            $this->executeMysqlQuery('DELETE FROM sys_registry WHERE entry_namespace = \'installUpdate\' AND (entry_key LIKE \'%ext_upgrade%\' OR entry_key LIKE \'%RsaauthExtractionUpdate%\')');
            $output = $this->executeConsoleCommand('upgrade:run', ['all', '--deny', 'all', '--confirm', 'confirmableWizard']);
            $this->assertStringContainsString('Skipped wizard "anotherConfirmableUpgradeWizard"', $output);
            $this->assertStringContainsString('Successfully executed upgrade wizard "confirmableWizard"', $output);
            $this->assertStringContainsString('Successfully executed upgrade wizard "normalWizard"', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_upgrade');
        }
    }

    /**
     * @test
     */
    public function rowUpdaterCanBeForced(): void
    {
        $output = $this->executeConsoleCommand('upgrade:list');
        $this->assertStringContainsString('None', $output);
        $output = $this->executeConsoleCommand('upgrade:list', ['--all']);
        $this->assertStringContainsString('Row Updaters:', $output);
        $this->assertStringContainsString(WorkspaceVersionRecordsMigration::class, $output);
        $output = $this->executeConsoleCommand('upgrade:run', ['databaseRowsUpdateWizard']);
        $this->assertStringContainsString('Upgrade wizard "databaseRowsUpdateWizard" was skipped because no operation is needed', $output);
        $output = $this->executeConsoleCommand('upgrade:run', ['databaseRowsUpdateWizard', '--force-row-updater', WorkspaceVersionRecordsMigration::class]);
        $this->assertStringContainsString('Successfully executed upgrade wizard "databaseRowsUpdateWizard"', $output);
    }
}
