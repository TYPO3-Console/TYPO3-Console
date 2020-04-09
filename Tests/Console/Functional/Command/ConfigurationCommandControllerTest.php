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

class ConfigurationCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function configurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:show', ['BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:showlocal', ['BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShownAsJson()
    {
        $output = $this->executeConsoleCommand('configuration:showlocal', ['BE/installToolPassword', '--json']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($config['BE']['installToolPassword'], \json_decode($output));
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShownAsJson()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['BE/installToolPassword', '--json']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($config['BE']['installToolPassword'], \json_decode($output));
    }

    /**
     * @test
     */
    public function activeConfigurationReflectsRealState()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['SYS/lang/format/priority', '--json']);
        $this->assertSame('xlf,xml', \json_decode($output));
    }

    /**
     * @test
     */
    public function configurationCanBeSet()
    {
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $oldPassword = $config['BE']['installToolPassword'];
        $this->executeConsoleCommand('configuration:set', ['BE/installToolPassword', 'foobar']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('foobar', $config['BE']['installToolPassword']);
        $this->executeConsoleCommand('configuration:set', ['BE/installToolPassword', $oldPassword]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($oldPassword, $config['BE']['installToolPassword']);
    }

    /**
     * @test
     */
    public function configurationCanBeRemovedAndSetAgainWithoutKeyPresent()
    {
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $oldPassword = $config['BE']['installToolPassword'];
        $this->executeConsoleCommand('configuration:remove', ['BE/installToolPassword', '--force']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertArrayNotHasKey('installToolPassword', $config['BE']);
        $this->executeConsoleCommand('configuration:set', ['BE/installToolPassword', $oldPassword]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($oldPassword, $config['BE']['installToolPassword']);
    }

    /**
     * @test
     */
    public function numericalIndexedArraysCanBeSet()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/lang/availableLanguages/0', 'fr_FR']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('fr_FR', $config['EXTCONF']['lang']['availableLanguages'][0]);
    }

    /**
     * @test
     */
    public function associativeArraysCanBeSet()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/bar', 'baz']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('baz', $config['EXTCONF']['foo']['bar']);
    }

    /**
     * @test
     */
    public function arraysCanBeSetAsJson()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/baz', '["baz"]', '--json']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame(['baz'], $config['EXTCONF']['foo']['baz']);
    }

    /**
     * @test
     */
    public function booleanCanBeSetAsJson()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/bool', 'true', '--json']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertTrue($config['EXTCONF']['foo']['bool']);
    }

    /**
     * @test
     */
    public function nullCanBeSetAsJson()
    {
        $this->executeConsoleCommand('configuration:set', ['EXTCONF/foo/null', 'null', '--json']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
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
            $this->assertContains('Could not decode value', $e->getOutputMessage());
        }
    }
}
