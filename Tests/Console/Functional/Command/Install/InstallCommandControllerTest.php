<?php
declare(strict_types=1);
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
    public function setupCommandWorksOnSqLiteWithoutErrors()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Cannot execute SQLite test, when SQLite module is disabled');
        }
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php');
        @unlink(getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php');
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--database-driver',
                'pdo_sqlite',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Set up extensions', $output);
        $this->assertFileNotExists(getenv('TYPO3_PATH_WEB') . '/.htaccess');
        $this->assertFileNotExists(getenv('TYPO3_PATH_WEB') . '/web.config');
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
                '--no-interaction',
                '--skip-extension-setup',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('<comment>Skipped</comment> Set up extensions', $output);
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
                '--no-interaction',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Set up extensions', $output);
        $this->assertFileNotExists(getenv('TYPO3_PATH_WEB') . '/.htaccess');
        $this->assertFileNotExists(getenv('TYPO3_PATH_WEB') . '/web.config');
    }

    /**
     * @test
     */
    public function setupEvaluatesStepFileIfGiven()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--skip-integrity-check',
            ],
            [
                'TYPO3_INSTALL_SETUP_STEPS' => __DIR__ . '/../../Fixtures/Install/custom-install.yaml',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Custom step', $output);
        $this->assertNotContains('Set up extensions', $output);
    }

    /**
     * @test
     */
    public function deprecatedSetupOptionNonInteractiveWorks()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--non-interactive',
                '--skip-integrity-check',
            ],
            [
                'TYPO3_INSTALL_SETUP_STEPS' => __DIR__ . '/../../Fixtures/Install/custom-install.yaml',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Custom step', $output);
        $this->assertContains('Option --non-interactive is deprecated. Please use --no-interaction instead.', $output);
        $this->assertNotContains('Set up extensions', $output);
    }

    /**
     * @test
     */
    public function setupCreatesHtaccessIfRequested()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--skip-integrity-check',
                '--site-setup-type',
                'no',
                '--web-server-config',
                'apache',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertFileExists(getenv('TYPO3_PATH_WEB') . '/.htaccess');
        unlink(getenv('TYPO3_PATH_WEB') . '/.htaccess');
        $this->assertFileNotExists(getenv('TYPO3_PATH_WEB') . '/web.config');
    }

    /**
     * @test
     */
    public function setupCreatesWebConfigIfRequested()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--skip-integrity-check',
                '--site-setup-type',
                'no',
                '--web-server-config',
                'iis',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertFileExists(getenv('TYPO3_PATH_WEB') . '/web.config');
        unlink(getenv('TYPO3_PATH_WEB') . '/web.config');
        $this->assertFileNotExists(getenv('TYPO3_PATH_WEB') . '/.htaccess');
    }

    /**
     * @test
     */
    public function setupEvaluatesStepFileIfGivenWithRelativePath()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--skip-integrity-check',
            ],
            [
                'TYPO3_INSTALL_SETUP_STEPS' => 'Tests/Console/Functional/Fixtures/Install/custom-install.yaml',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Custom step', $output);
        $this->assertNotContains('Set up extensions', $output);
    }

    /**
     * @test
     */
    public function setupEvaluatesStepFileIfGivenWithRelativePathAsCommandOption()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--skip-integrity-check',
                '--install-steps-config',
                'Tests/Console/Functional/Fixtures/Install/custom-install.yaml',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Custom step', $output);
        $this->assertNotContains('Set up extensions', $output);
    }

    /**
     * @test
     */
    public function individualStepFilesCanImportDefaultsAndSkipDefaultActions()
    {
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--skip-integrity-check',
            ],
            [
                'TYPO3_INSTALL_SETUP_STEPS' => 'Tests/Console/Functional/Fixtures/Install/custom-install-import.yaml',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
        $this->assertContains('Check environment and create folders', $output);
        $this->assertContains('Custom step', $output);
        $this->assertNotContains('Set up extensions', $output);
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
    public function siteSetupCreatedHomepageOnlyOnce()
    {
        $queryResult = $this->executeMysqlQuery('SELECT count(*) FROM `pages`;');
        $this->assertSame('1', preg_replace('/\s+/', '', $queryResult));
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
    public function packageStatesFileIsCreatedWithoutDependentExtensions()
    {
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        self::installFixtureExtensionCode('ext_no_dep');
        self::installFixtureExtensionCode('ext_with_dep');
        @unlink($packageStatesFile);
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        $this->assertArrayNotHasKey('reports', $packageConfig['packages']);
        self::removeFixtureExtensionCode('ext_no_dep');
        self::removeFixtureExtensionCode('ext_with_dep');
        $this->executeConsoleCommand('install:generatepackagestates');
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
        $this->assertArrayNotHasKey('reports', $packageConfig['packages']);
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
        @unlink($packageStatesFile . '_');
        $this->assertArrayHasKey('reports', $packageConfig['packages']);
    }
}
