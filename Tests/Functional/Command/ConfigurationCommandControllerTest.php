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

class ConfigurationCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function configurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:show', ['--path' => 'BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:showlocal', ['--path' => 'BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShownAsJson()
    {
        $output = $this->executeConsoleCommand('configuration:showlocal', ['--path' => 'BE/installToolPassword', '--json' => true]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($config['BE']['installToolPassword'], \json_decode($output));
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShown()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['--path' => 'BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShownAsJson()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['--path' => 'BE/installToolPassword', '--json' => true]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($config['BE']['installToolPassword'], \json_decode($output));
    }

    /**
     * @test
     */
    public function activeConfigurationReflectsRealState()
    {
        $output = $this->executeConsoleCommand('configuration:showactive', ['--path' => 'SYS/caching/cacheConfigurations/extbase_reflection/backend', '--json' => true]);
        $this->assertSame('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', \json_decode($output));
    }

    /**
     * @test
     */
    public function configurationCanBeSet()
    {
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $oldPassword = $config['BE']['installToolPassword'];
        $this->executeConsoleCommand('configuration:set', ['--path' => 'BE/installToolPassword', '--value' => 'foobar']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('foobar', $config['BE']['installToolPassword']);
        $this->executeConsoleCommand('configuration:set', ['--path' => 'BE/installToolPassword', '--value' => $oldPassword]);
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
        $this->executeConsoleCommand('configuration:remove', ['--paths' => 'BE/installToolPassword', '--force' => true]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertArrayNotHasKey('installToolPassword', $config['BE']);
        $this->executeConsoleCommand('configuration:set', ['--path' => 'BE/installToolPassword', '--value' => $oldPassword]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($oldPassword, $config['BE']['installToolPassword']);
    }

    /**
     * @test
     */
    public function numericalIndexedArraysCanBeSet()
    {
        $this->executeConsoleCommand('configuration:set', ['--path' => 'EXTCONF/lang/availableLanguages/0', '--value' => 'fr_FR']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('fr_FR', $config['EXTCONF']['lang']['availableLanguages'][0]);
    }

    /**
     * @test
     */
    public function associativeArraysCanBeSet()
    {
        $this->executeConsoleCommand('configuration:set', ['--path' => 'EXTCONF/foo/bar', '--value' => 'baz']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('baz', $config['EXTCONF']['foo']['bar']);
    }
}
