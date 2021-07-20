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
        $this->assertStringContainsString('Extension key', $output);
        $this->assertStringContainsString('extbase', $output);
        $this->assertStringContainsString('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListRawShowsActiveAndInactiveExtensionsButNoHeader()
    {
        $output = $this->executeConsoleCommand('extension:list', ['--raw']);
        $this->assertStringNotContainsString('Extension key', $output);
        $this->assertStringContainsString('extbase', $output);
        $this->assertStringContainsString('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListCanShowOnlyActiveExtensions()
    {
        $output = $this->executeConsoleCommand('extension:list', ['--active', '--raw']);
        $this->assertStringContainsString('extbase', $output);
        $this->assertStringNotContainsString('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionListCanShowOnlyInActiveExtensions()
    {
        $output = $this->executeConsoleCommand('extension:list', ['--inactive', '--raw']);
        $this->assertStringNotContainsString('extbase', $output);
        $this->assertStringContainsString('filemetadata', $output);
    }

    /**
     * @test
     */
    public function extensionSetupActiveWorksWhenExtensionChecksConfigInExtLocalConf()
    {
        self::installFixtureExtensionCode('ext_config');
        try {
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
            $output = $this->executeConsoleCommand('extension:setupactive');
            $this->assertStringContainsString('ext_config', $output);
            $this->assertStringContainsString('are now set up.', $output);
            $config = @\json_decode(trim($this->executeConsoleCommand('configuration:showlocal', ['EXTENSIONS', '--json'])), true);
            $this->assertArrayHasKey('ext_config', $config);
        } finally {
            self::removeFixtureExtensionCode('ext_config');
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
        try {
            self::installFixtureExtensionCode('ext_test');
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);

            $output = $this->executeConsoleCommand('extension:setupactive');
            $this->assertStringContainsString('ext_test', $output);
            $this->assertStringContainsString('are now set up.', $output);

            $output = $this->executeConsoleCommand('database:updateschema');
            $this->assertStringContainsString('No schema updates were performed for update types:', $output);
        } finally {
            self::removeFixtureExtensionCode('ext_test');
            $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
            $this->restoreDatabase();
        }
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
        $this->assertStringContainsString('are now set up.', $output);

        $filesystem->chmod(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php', 0664);
        $this->restoreDatabase();
    }
}
