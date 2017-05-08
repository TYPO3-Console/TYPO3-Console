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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;

class UpgradeCommandControllerTest extends AbstractCommandTest
{
    /**
     * @var string
     */
    private $consoleRootPath;

    /**
     * @var string
     */
    private $typo3RootPath;

    /**
     * @test
     */
    public function canCheckExtensionConstraints()
    {
        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints');
        $this->assertContains('All third party extensions claim to be compatible with TYPO3 version', $output);
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsReturnsErrorCodeOnFailure()
    {
        $this->installFixtureExtensionCode('ext_test');
        $this->commandDispatcher->executeCommand('extension:activate', ['--extension-keys' => 'ext_test']);
        try {
            $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--typo3-version' => '3.6.0']);
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
            $this->assertContains('"ext_test" requires TYPO3 versions 4.5.0', $e->getOutputMessage());
        }
        $this->commandDispatcher->executeCommand('extension:deactivate', ['--extension-keys' => 'ext_test']);
        $this->removeFixtureExtensionCode('ext_test');
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsIssuesWarningForInvalidExtensionKeys()
    {
        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--extension-keys' => 'foo,bar']);
        $this->assertContains('Extension "foo" is not found in the system', $output);
        $this->assertContains('Extension "bar" is not found in the system', $output);
    }

    /**
     * @test
     */
    public function canPerformTypo3Upgrade()
    {
        $instancePath = dirname(dirname(dirname(__DIR__))) . '/.Build/upgrade_test';
        try {
            $this->setUpNewTypo3Instance($instancePath);
            $this->upgradeCodeToTypo3Version(getenv('TYPO3_VERSION'));

            $output = $this->commandDispatcher->executeCommand('upgrade:list');
            $this->assertContains('Wizards scheduled for execution', $output);
            $output = $this->commandDispatcher->executeCommand('upgrade:all');
            $this->assertContains('Initiating TYPO3 upgrade', $output);
            $this->assertContains('Successfully upgraded TYPO3 to version', $output);
        } catch (\Throwable $e) {
            throw $e;
        } catch (\Exception $e) {
            // @deprecated can be removed once 7.6 support has been removed
            throw $e;
        } finally {
            $this->tearDownTypo3Instance($instancePath);
        }
    }

    /**
     * @param string $instancePath
     */
    private function setUpNewTypo3Instance($instancePath)
    {
        $this->consoleRootPath = getenv('TYPO3_PATH_COMPOSER_ROOT');
        $this->typo3RootPath = getenv('TYPO3_PATH_ROOT');
        $this->commandDispatcher = CommandDispatcher::createFromTestRun($instancePath . '/vendor/helhum/typo3-console/Scripts/typo3cms');
        if (!is_dir($instancePath)) {
            mkdir($instancePath);
        }
        putenv('TYPO3_PATH_COMPOSER_ROOT=' . $instancePath);
        putenv('TYPO3_PATH_ROOT=' . $instancePath . '/web');
        putenv('TYPO3_PATH_WEB=' . $instancePath . '/web');
        putenv('TYPO3_INSTALL_DB_DBNAME=' . getenv('TYPO3_INSTALL_DB_DBNAME') . '_up');
        chdir($instancePath);

        file_put_contents($instancePath . '/composer.json', '{}');
        $this->executeComposerCommand(['config', 'extra.typo3/cms.cms-package-dir', '{$vendor-dir}/typo3/cms']);
        $this->executeComposerCommand(['config', 'extra.typo3/cms.web-dir', 'web']);
        $this->executeComposerCommand(['config', 'extra.helhum/typo3-console.install-extension-dummy', '0']);
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->executeComposerCommand(['config', 'repositories.console', '{"type": "vcs", "url": "' . addcslashes($this->consoleRootPath, '\\') . '"}']);
            $output = $this->executeComposerCommand(['require', 'typo3/cms=^7.6.18', 'helhum/typo3-console=dev-ci-test']);
            $output = preg_replace('/[^\x09-\x0d\x1b\x20-\xff]/', '', $output);
            $this->assertContains('Copied typo3 directory to document root', $output);
        } else {
            $this->copyDirectory($this->consoleRootPath, $instancePath . '/typo3_console', ['.Build', '.git']);
            $this->executeComposerCommand(['config', 'repositories.console', '{"type": "path", "url": "typo3_console", "options": {"symlink": false}}']);
            $this->executeComposerCommand(['require', 'typo3/cms=^7.6.18', 'helhum/typo3-console=@dev']);
        }

        $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . getenv('TYPO3_INSTALL_DB_DBNAME'), false);
        try {
            $output = $this->commandDispatcher->executeCommand(
                'install:setup',
                [
                    '--non-interactive' => true,
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
        } catch (FailedSubProcessCommandException $e) {
            $this->fail(sprintf('install:setup (%s) failed with: "%s", "%s"', $e->getCommandLine(), $e->getOutputMessage(), $e->getErrorMessage()));
        }
    }

    /**
     * @param string $typo3Version
     */
    private function upgradeCodeToTypo3Version($typo3Version)
    {
        $output = $this->executeComposerCommand(['require', 'typo3/cms=' . $typo3Version, '--update-with-dependencies']);
        if (DIRECTORY_SEPARATOR === '\\') {
            $output = preg_replace('/[^\x09-\x0d\x1b\x20-\xff]/', '', $output);
        }
        $this->assertContains('Generating autoload files', $output);
    }

    /**
     * @param string $instancePath
     * @throws \Exception
     */
    private function tearDownTypo3Instance($instancePath)
    {
        putenv('TYPO3_PATH_COMPOSER_ROOT=' . $this->consoleRootPath);
        putenv('TYPO3_PATH_ROOT=' . $this->typo3RootPath);
        putenv('TYPO3_PATH_WEB=' . $this->typo3RootPath);
        chdir($this->consoleRootPath);
        try {
            $this->removeDirectory($instancePath);
        } catch (\Exception $e) {
            // Ignore this exception on Windows
            if (DIRECTORY_SEPARATOR !== '\\') {
                throw $e;
            }
        }
    }
}
