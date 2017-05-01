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

use Symfony\Component\Filesystem\Filesystem;

class ExtensionCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function extensionListShowsActiveAndInactiveExtensions()
    {
        $output = $this->commandDispatcher->executeCommand('extension:list');
        $this->assertContains('Extension key', $output);
        $this->assertContains('extbase', $output);
        $this->assertContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListRawShowsActiveAndInactiveExtensionsButNoHeader()
    {
        $output = $this->commandDispatcher->executeCommand('extension:list', ['--raw' => true]);
        $this->assertNotContains('Extension key', $output);
        $this->assertContains('extbase', $output);
        $this->assertContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListCanShowOnlyActiveExtensions()
    {
        $output = $this->commandDispatcher->executeCommand('extension:list', ['--active' => true, '--raw' => true]);
        $this->assertContains('extbase', $output);
        $this->assertNotContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListCanShowOnlyInActiveExtensions()
    {
        $output = $this->commandDispatcher->executeCommand('extension:list', ['--inactive' => true, '--raw' => true]);
        $this->assertNotContains('extbase', $output);
        $this->assertContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionActivateCallsSchemaUpdate()
    {
        $this->backupDatabase();
        $this->installFixtureExtensionCode('ext_test');

        $output = $this->commandDispatcher->executeCommand('extension:activate', ['--extension-keys' => 'ext_test']);
        $this->assertContains('Extension "ext_test" is now active.', $output);
        $this->assertContains('Extension "ext_test" is now set up.', $output);

        $output = $this->commandDispatcher->executeCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->commandDispatcher->executeCommand('extension:deactivate', ['--extension-keys' => 'ext_test']);
        $this->assertContains('Extension "ext_test" is now inactive.', $output);

        $this->removeFixtureExtensionCode('ext_test');
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function extensionActivateOnAlreadyInstalledExtensionDoesNotDestroyCurrentSchema()
    {
        $this->backupDatabase();
        $this->installFixtureExtensionCode('ext_test');

        $output = $this->commandDispatcher->executeCommand('extension:activate', ['--extension-keys' => 'ext_test']);
        $this->assertContains('Extension "ext_test" is now active.', $output);
        $this->assertContains('Extension "ext_test" is now set up.', $output);

        $output = $this->commandDispatcher->executeCommand('extension:activate', ['--extension-keys' => 'core']);
        $this->assertNotContains('is now active.', $output);
        $this->assertContains('Extension "core" is now set up.', $output);

        $output = $this->commandDispatcher->executeCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->commandDispatcher->executeCommand('extension:deactivate', ['--extension-keys' => 'ext_test']);
        $this->assertContains('Extension "ext_test" is now inactive.', $output);

        $this->removeFixtureExtensionCode('ext_test');
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function extensionSetupActivePerformsSchemaUpdate()
    {
        $this->backupDatabase();
        $this->installFixtureExtensionCode('ext_test');
        $this->commandDispatcher->executeCommand('install:generatepackagestates', ['--activate-default' => true]);

        $output = $this->commandDispatcher->executeCommand('extension:setupactive');
        $this->assertContains('ext_test', $output);
        $this->assertContains('are now set up.', $output);

        $output = $this->commandDispatcher->executeCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->commandDispatcher->executeCommand('extension:deactivate', ['--extension-keys' => 'ext_test']);
        $this->assertContains('Extension "ext_test" is now inactive.', $output);

        $this->removeFixtureExtensionCode('ext_test');
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function extensionSetupDoesNotWriteLocalConfiguration()
    {
        $this->backupDatabase();
        $filesystem = new Filesystem();
        $filesystem->chmod(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php', 0444);
        $output = $this->commandDispatcher->executeCommand('extension:setupactive');
        $this->assertContains('are now set up.', $output);

        $filesystem->chmod(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php', 0664);
        $this->restoreDatabase();
    }

    /**
     * @test
     * @deprecated can be removed once TYPO3 7.6 support is removed
     */
    public function dbalCacheTablesAreRespectedWhenDbalIsActive()
    {
        $output = $this->commandDispatcher->executeCommand('extension:list', ['--raw' => true]);
        if (strpos($output, 'dbal') === false) {
            // DBAL Extension is only available with TYPO3 versions 7.6.x
            return;
        }
        $this->backupDatabase();

        $output = $this->commandDispatcher->executeCommand('extension:activate', ['--extension-keys' => 'dbal,adodb']);
        $this->assertContains('Extensions "dbal", "adodb" are now active.', $output);

        $output = $this->commandDispatcher->executeCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->commandDispatcher->executeCommand('extension:deactivate', ['--extension-keys' => 'dbal,adodb']);
        $this->assertContains('Extensions "dbal", "adodb" are now inactive.', $output);

        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function canRemoveInactiveExtensions()
    {
        $this->copyDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext', getenv('TYPO3_PATH_ROOT') . '/typo3temp/sysext');

        $output = $this->commandDispatcher->executeCommand('extension:removeinactive', ['--force' => true]);
        $this->assertContains('filemetadata', $output);

        $this->copyDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3temp/sysext', getenv('TYPO3_PATH_ROOT') . '/typo3/sysext');
    }
}
