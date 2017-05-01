<?php
namespace Helhum\Typo3Console\Tests\Functional\Command\Install;

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

use Helhum\Typo3Console\Tests\Functional\Command\AbstractCommandTest;
use Symfony\Component\Process\ProcessBuilder;

class InstallCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function helpBeforeSetupDoesNotCreatePackageStatesFile()
    {
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php');
        $this->commandDispatcher->executeCommand('help');
        $this->assertFalse(file_exists(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php'));
    }

    /**
     * @test
     */
    public function setupCommandWorksWithoutErrors()
    {
        $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . $this->getDatabaseConnectionSettings()['dbName'], false);
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php');
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php');
        $settings = $this->getDatabaseConnectionSettings();
        $output = $this->commandDispatcher->executeCommand(
            'install:setup',
            [
                '--non-interactive' => true,
                '--database-user-name' => $settings['dbUser'],
                '--database-user-password' => $settings['dbPassword'],
                '--database-host-name' => 'localhost',
                '--database-port' => '3306',
                '--database-name' => $settings['dbName'],
                '--admin-user-name' => 'admin',
                '--admin-password' => 'password',
                '--site-name' => 'Travis Install',
                '--site-setup-type' => 'createsite',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
    }

    /**
     * @test
     */
    public function siteSetupCreatedHomepage()
    {
        $queryResult = $this->executeMysqlQuery('SELECT uid,title FROM `pages` LIMIT 1;');
        $this->assertSame('1Home', preg_replace('/\s+/', '', $queryResult));
    }

    /**
     * @test
     */
    public function folderStructureIsCreated()
    {
        $indexFile = getenv('TYPO3_PATH_ROOT') . '/typo3temp/index.html';
        @unlink($indexFile);
        $this->commandDispatcher->executeCommand(
            'install:fixfolderstructure'
        );
        $this->assertTrue(file_exists($indexFile));
    }

    /**
     * @test
     */
    public function packageStatesFileIsCreatedWithoutDefaultPackages()
    {
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        @unlink($packageStatesFile);
        $this->commandDispatcher->executeCommand(
            'install:generatepackagestates'
        );
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        if ($packageConfig['version'] === 5) {
            $this->assertArrayNotHasKey('reports', $packageConfig['packages']);
        } else {
            $this->assertSame('inactive', $packageConfig['packages']['reports']['state']);
        }
    }

    /**
     * @test
     */
    public function packageStatesFileIsCreatedWithDefaultPackages()
    {
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        @unlink($packageStatesFile);
        $this->commandDispatcher->executeCommand(
            'install:generatepackagestates',
            [
                '--activate-default' => true,
            ]
        );
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        if ($packageConfig['version'] === 5) {
            $this->assertArrayHasKey('reports', $packageConfig['packages']);
        } else {
            $this->assertSame('active', $packageConfig['packages']['reports']['state']);
        }
    }

    /**
     * @test
     */
    public function packageStatesFileIsCreatedFromComposerRun()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // TODO: find out the reason, why composer cannot be executed using Symfony Process
            $this->markTestSkipped('Currently does not work on Windows');
        }
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        @unlink($packageStatesFile);
        $processBuilder = new ProcessBuilder();
        $processBuilder->setEnv('TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES', 'yes');
        $processBuilder->setEnv('TYPO3_CONSOLE_TEST_SETUP', 'yes');
        $processBuilder->setEnv('TYPO3_ACTIVATE_DEFAULT_FRAMEWORK_EXTENSIONS', 'yes');
        $processBuilder->setPrefix('composer');
        $processBuilder->add('dump-autoload');
        $processBuilder->add('-vv');
        $processBuilder->add('-d');
        $processBuilder->add(dirname(dirname(dirname(dirname(__DIR__)))));
        $composerProcess = $processBuilder->getProcess();
        $composerProcess->run();
        $this->assertTrue($composerProcess->isSuccessful());
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        if ($packageConfig['version'] === 5) {
            $this->assertArrayHasKey('reports', $packageConfig['packages']);
        } else {
            $this->assertSame('active', $packageConfig['packages']['reports']['state']);
        }
    }
}
