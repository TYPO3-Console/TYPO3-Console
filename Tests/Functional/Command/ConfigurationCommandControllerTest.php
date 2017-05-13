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
        $output = $this->commandDispatcher->executeCommand('configuration:show', ['--path' => 'BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShown()
    {
        $output = $this->commandDispatcher->executeCommand('configuration:showlocal', ['--path' => 'BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function localConfigurationCanBeShownAsJson()
    {
        $output = $this->commandDispatcher->executeCommand('configuration:showlocal', ['--path' => 'BE/installToolPassword', '--json' => true]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains(\json_encode($config['BE']['installToolPassword']), $output);
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShown()
    {
        $output = $this->commandDispatcher->executeCommand('configuration:showactive', ['--path' => 'BE/installToolPassword']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains($config['BE']['installToolPassword'], $output);
    }

    /**
     * @test
     */
    public function activeConfigurationCanBeShownAsJson()
    {
        $output = $this->commandDispatcher->executeCommand('configuration:showactive', ['--path' => 'BE/installToolPassword', '--json' => true]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertContains(\json_encode($config['BE']['installToolPassword']), $output);
    }

    /**
     * @test
     */
    public function configurationCanBeSet()
    {
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $oldPassword = $config['BE']['installToolPassword'];
        $this->commandDispatcher->executeCommand('configuration:set', ['--path' => 'BE/installToolPassword', '--value' => 'foobar']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('foobar', $config['BE']['installToolPassword']);
        $this->commandDispatcher->executeCommand('configuration:set', ['--path' => 'BE/installToolPassword', '--value' => $oldPassword]);
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
        $this->commandDispatcher->executeCommand('configuration:remove', ['--path' => 'BE/installToolPassword', '--force' => true]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertArrayNotHasKey('installToolPassword', $config['BE']);
        $this->commandDispatcher->executeCommand('configuration:set', ['--path' => 'BE/installToolPassword', '--value' => $oldPassword]);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame($oldPassword, $config['BE']['installToolPassword']);
    }

    /**
     * @test
     */
    public function numericalIndexedArraysCanBeSet()
    {
        $this->commandDispatcher->executeCommand('configuration:set', ['--path' => 'EXTCONF/lang/availableLanguages/0', '--value' => 'fr_FR']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('fr_FR', $config['EXTCONF']['lang']['availableLanguages'][0]);
    }

    /**
     * @test
     */
    public function associativeArraysCanBeSet()
    {
        $this->commandDispatcher->executeCommand('configuration:set', ['--path' => 'EXTCONF/foo/bar', '--value' => 'baz']);
        $config = require getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $this->assertSame('baz', $config['EXTCONF']['foo']['bar']);
    }
}
