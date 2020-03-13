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

use Symfony\Component\Filesystem\Filesystem;

class ExtensionCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function extensionListShowsActiveAndInactiveExtensions()
    {
        $output = $this->executeConsoleCommand('extension:list');
        $this->assertContains('Extension key', $output);
        $this->assertContains('extbase', $output);
        $this->assertContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListRawShowsActiveAndInactiveExtensionsButNoHeader()
    {
        $output = $this->executeConsoleCommand('extension:list', ['--raw']);
        $this->assertNotContains('Extension key', $output);
        $this->assertContains('extbase', $output);
        $this->assertContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListCanShowOnlyActiveExtensions()
    {
        $output = $this->executeConsoleCommand('extension:list', ['--active', '--raw']);
        $this->assertContains('extbase', $output);
        $this->assertNotContains('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListCanShowOnlyInActiveExtensions()
    {
        $output = $this->executeConsoleCommand('extension:list', ['--inactive', '--raw']);
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

        $output = $this->executeConsoleCommand('extension:activate', ['ext_test']);
        $this->assertContains('Extension "ext_test" is now active.', $output);
        $this->assertContains('Extension "ext_test" is now set up.', $output);

        $output = $this->executeConsoleCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->executeConsoleCommand('extension:deactivate', ['ext_test']);
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

        $output = $this->executeConsoleCommand('extension:activate', ['ext_test']);
        $this->assertContains('Extension "ext_test" is now active.', $output);
        $this->assertContains('Extension "ext_test" is now set up.', $output);

        $output = $this->executeConsoleCommand('extension:activate', ['core']);
        $this->assertNotContains('is now active.', $output);
        $this->assertContains('Extension "core" is now set up.', $output);

        $output = $this->executeConsoleCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->executeConsoleCommand('extension:deactivate', ['ext_test']);
        $this->assertContains('Extension "ext_test" is now inactive.', $output);

        $this->removeFixtureExtensionCode('ext_test');
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function extensionActivateWorksWhenExtensionChecksConfigInExtLocalConf()
    {
        $this->installFixtureExtensionCode('ext_config');
        try {
            $output = $this->executeConsoleCommand('extension:activate', ['ext_config']);
            $this->assertContains('Extension "ext_config" is now active.', $output);
            $this->assertContains('Extension "ext_config" is now set up.', $output);
            $config = @\json_decode(trim($this->executeConsoleCommand('configuration:showlocal', ['EXTENSIONS', '--json'])), true);
            $this->assertArrayHasKey('ext_config', $config);
        } finally {
            $this->executeConsoleCommand('extension:deactivate', ['ext_config']);
            $this->executeConsoleCommand('configuration:remove', ['EXTENSIONS/ext_config', '--force']);
            $this->removeFixtureExtensionCode('ext_config');
        }
    }

    /**
     * @test
     */
    public function extensionSetupActiveWorksWhenExtensionChecksConfigInExtLocalConf()
    {
        $this->installFixtureExtensionCode('ext_config');
        try {
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
            $output = $this->executeConsoleCommand('extension:setupactive');
            $this->assertContains('ext_config', $output);
            $this->assertContains('are now set up.', $output);
            $config = @\json_decode(trim($this->executeConsoleCommand('configuration:showlocal', ['EXTENSIONS', '--json'])), true);
            $this->assertArrayHasKey('ext_config', $config);
        } finally {
            $this->removeFixtureExtensionCode('ext_config');
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
            $this->executeConsoleCommand('configuration:remove', ['EXTENSIONS/ext_config', '--force']);
        }
    }

    /**
     * @test
     */
    public function extensionSetupActivePerformsSchemaUpdate()
    {
        $this->backupDatabase();
        $this->installFixtureExtensionCode('ext_test');
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);

        $output = $this->executeConsoleCommand('extension:setupactive');
        $this->assertContains('ext_test', $output);
        $this->assertContains('are now set up.', $output);

        $output = $this->executeConsoleCommand('database:updateschema');
        $this->assertContains('No schema updates were performed for update types:', $output);

        $output = $this->executeConsoleCommand('extension:deactivate', ['ext_test']);
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
        $output = $this->executeConsoleCommand('extension:setupactive');
        $this->assertContains('are now set up.', $output);

        $filesystem->chmod(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php', 0664);
        $this->restoreDatabase();
    }

    /**
     * @test
     */
    public function canRemoveInactiveExtensions()
    {
        $this->copyDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext', getenv('TYPO3_PATH_ROOT') . '/typo3temp/sysext');

        $output = $this->executeConsoleCommand('extension:removeinactive', ['--force']);
        $this->assertContains('filemetadata', $output);

        $this->copyDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3temp/sysext', getenv('TYPO3_PATH_ROOT') . '/typo3/sysext');
        $this->removeDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3temp/sysext');
    }
}
