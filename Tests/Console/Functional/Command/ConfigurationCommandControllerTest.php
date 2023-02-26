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
use Helhum\Typo3Console\Typo3CompatibilityBridge;

class ConfigurationCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function configurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:show', ['BE/installToolPassword']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertStringContainsString($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function configurationShowsDiff()
    {
        $output = $this->executeConsoleCommand('configuration:show', ['SC_OPTIONS/"t3lib/class.t3lib_tcemain.php"']);
        $this->assertStringContainsString('processDatamapClass', $output);
        $this->assertStringContainsString('++ overridden configuration', $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:showlocal', ['BE/installToolPassword']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertStringContainsString($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShownAsJson()
    {
        $output = $this->executeConsoleCommand('configuration:showlocal', ['BE/installToolPassword', '--json']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame($config['BE']['installToolPassword'], \json_decode($output));
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['BE/installToolPassword']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertStringContainsString($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShownAsJson()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['BE/installToolPassword', '--json']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame($config['BE']['installToolPassword'], \json_decode($output));
    }

    /**
     * @test
     */
    public function configurationCanBeSet()
    {
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $oldPassword = $config['BE']['installToolPassword'];
        $this->executeConsoleCommand('configuration:set', ['BE/installToolPassword', 'foobar']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame('foobar', $config['BE']['installToolPassword']);
        $this->executeConsoleCommand('configuration:set', ['BE/installToolPassword', $oldPassword]);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame($oldPassword, $config['BE']['installToolPassword']);
    }

    /**
     * @test
     */
    public function configurationCanBeRemovedAndSetAgainWithoutKeyPresent()
    {
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $oldPassword = $config['BE']['installToolPassword'];
        $this->executeConsoleCommand('configuration:remove', ['BE/installToolPassword', '--force']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertArrayNotHasKey('installToolPassword', $config['BE']);
        $this->executeConsoleCommand('configuration:set', ['BE/installToolPassword', $oldPassword]);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame($oldPassword, $config['BE']['installToolPassword']);
    }

    /**
     * @test
     */
    public function numericalIndexedArraysCanBeSet()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/lang/availableLanguages/0', 'fr_FR']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame('fr_FR', $config['EXTCONF']['lang']['availableLanguages'][0]);
    }

    /**
     * @test
     */
    public function associativeArraysCanBeSet()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/bar', 'baz']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame('baz', $config['EXTCONF']['foo']['bar']);
    }

    /**
     * @test
     */
    public function arraysCanBeSetAsJson()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/baz', '["baz"]', '--json']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertSame(['baz'], $config['EXTCONF']['foo']['baz']);
    }

    /**
     * @test
     */
    public function booleanCanBeSetAsJson()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/bool', 'true', '--json']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertTrue($config['EXTCONF']['foo']['bool']);
    }

    /**
     * @test
     */
    public function nullCanBeSetAsJson()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/null', 'null', '--json']);
        $config = require Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        $this->assertNull($config['EXTCONF']['foo']['null']);
    }

    /**
     * @test
     */
    public function invalidJsonOutputsErrorMessage()
    {
        try {
            $this->commandDispatcher->executeCommand('configuration:set', ['EXTCONF/foo/bla', '[asd{', '--json']);
        } catch (FailedSubProcessCommandException $e) {
            $this->assertStringContainsString('Could not decode value', $e->getOutputMessage());
        }
    }
}
