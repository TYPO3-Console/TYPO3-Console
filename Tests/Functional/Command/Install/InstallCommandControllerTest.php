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

class InstallCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function helpBeforeSetupDoesNotCreatePackageStatesFile()
    {
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php');
        $this->executeConsoleCommand('help');
        $this->assertFalse(file_exists(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php'));
    }

    /**
     * @test
     */
    public function setupCommandDoesNotSetupExtensionsIfRequested()
    {
        $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php');
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php');
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--non-interactive',
                '--skip-extension-setup',
                '--database-user-name' => getenv('TYPO3_INSTALL_DB_USER'),
                '--database-user-password' => getenv('TYPO3_INSTALL_DB_PASSWORD'),
                '--database-host-name' => 'localhost',
                '--database-port' => '3306',
                '--database-name' => getenv('TYPO3_INSTALL_DB_DBNAME'),
                '--admin-user-name' => 'admin',
                '--admin-password' => 'password',
                '--site-name' => 'Travis Install',
                '--site-setup-type' => 'createsite',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertNotContains('Set up extensions', $output);
    }

    /**
     * @test
     */
    public function setupCommandWorksWithoutErrors()
    {
        $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php');
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php');
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--non-interactive',
                '--database-user-name' => getenv('TYPO3_INSTALL_DB_USER'),
                '--database-user-password' => getenv('TYPO3_INSTALL_DB_PASSWORD'),
                '--database-host-name' => 'localhost',
                '--database-port' => '3306',
                '--database-name' => getenv('TYPO3_INSTALL_DB_DBNAME'),
                '--admin-user-name' => 'admin',
                '--admin-password' => 'password',
                '--site-name' => 'Travis Install',
                '--site-setup-type' => 'createsite',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Set up extensions', $output);
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
        $this->executeConsoleCommand('install:fixfolderstructure');
        $this->assertTrue(file_exists($indexFile));
    }

    /**
     * @test
     */
    public function packageStatesFileIsCreatedWithoutDefaultPackages()
    {
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        @unlink($packageStatesFile);
        $this->executeConsoleCommand('install:generatepackagestates');
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
        copy($packageStatesFile, $packageStatesFile . '_');
        @unlink($packageStatesFile);
        $this->executeConsoleCommand('install:generatepackagestates', ['--activate-default']);
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        copy($packageStatesFile . '_', $packageStatesFile);
        if ($packageConfig['version'] === 5) {
            $this->assertArrayHasKey('reports', $packageConfig['packages']);
        } else {
            $this->assertSame('active', $packageConfig['packages']['reports']['state']);
        }
    }
}
