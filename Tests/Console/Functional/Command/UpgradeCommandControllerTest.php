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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class UpgradeCommandControllerTest extends AbstractCommandTest
{
    /**
     * @var string
     */
    private $consoleRootPath;

    /**
     * @var string
     */
    private $upgradeInstancePath;

    /**
     * @var string
     */
    private $upgradeInstanceDatabase;

    /**
     * @test
     */
    public function canCheckExtensionConstraints()
    {
        $output = $this->executeConsoleCommand('upgrade:checkextensionconstraints');
        $this->assertContains('All third party extensions claim to be compatible with TYPO3 version', $output);
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsReturnsErrorCodeOnFailure()
    {
        $this->installFixtureExtensionCode('ext_test');
        $this->executeConsoleCommand('install:generatepackagestates');
        $this->executeConsoleCommand('extension:setup', ['ext_test']);
        try {
            $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--typo3-version' => '3.6.0']);
            $this->fail('upgrade:checkextensionconstraints should have failed');
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
            $this->assertContains('"ext_test" requires TYPO3 versions 4.5.0', $e->getOutputMessage());
        } finally {
            $this->removeFixtureExtensionCode('ext_test');
            $this->executeConsoleCommand('install:generatepackagestates');
        }
    }

    /**
     * @test
     */
    public function checkExtensionCompatibilityReportsBrokenCodeInExtTables()
    {
        $this->installFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');

        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', ['ext_broken_ext_tables']);
        $this->assertSame('false', $output);

        $this->removeFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');
    }

    /**
     * @test
     */
    public function checkExtensionCompatibilityDoeNotReportBrokenCodeInExtTablesWithConfigOnlyCheck()
    {
        $this->installFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');

        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', ['ext_broken_ext_tables', '--config-only']);
        $this->assertSame('true', $output);

        $this->removeFixtureExtensionCode('ext_broken_ext_tables');
        $this->executeConsoleCommand('install:generatepackagestates');
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsIssuesWarningForInvalidExtensionKeys()
    {
        $output = $this->executeConsoleCommand('upgrade:checkextensionconstraints', ['foo,bar']);
        $this->assertContains('Extension "foo" is not found in the system', $output);
        $this->assertContains('Extension "bar" is not found in the system', $output);
    }

    /**
     * @test
     */
    public function canPerformTypo3Upgrade()
    {
        self::markTestSkipped('Needs rework');
        $this->consoleRootPath = getenv('TYPO3_PATH_COMPOSER_ROOT');
        $this->upgradeInstancePath = dirname(__DIR__, 4) . '/.Build/upgrade_test';
        $this->upgradeInstanceDatabase = getenv('TYPO3_INSTALL_DB_DBNAME') . '_up';
        try {
            $this->setUpNewTypo3Instance();
            $this->upgradeCodeToTypo3Version(getenv('TYPO3_VERSION'));

            $output = $this->executeConsoleCommand('upgrade:list');
            $this->assertContains('Wizards scheduled for execution', $output);
            $output = $this->executeConsoleCommand('upgrade:all');
            $this->assertContains('Initiating TYPO3 upgrade', $output);
            $this->assertContains('Successfully upgraded TYPO3 to version', $output);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->tearDownTypo3Instance();
        }
    }

    private function setUpNewTypo3Instance()
    {
        $this->commandDispatcher = CommandDispatcher::createFromTestRun($this->upgradeInstancePath . '/vendor/helhum/typo3-console/' . Application::COMMAND_NAME);
        if (!is_dir($this->upgradeInstancePath)) {
            mkdir($this->upgradeInstancePath);
        }

        file_put_contents(
            $this->upgradeInstancePath . '/composer.json',
            '{}'
        );
        $this->executeComposerCommand(['config', 'extra.typo3/cms.web-dir', 'public']);
        $this->copyDirectory($this->consoleRootPath, $this->upgradeInstancePath . '/typo3_console', ['.Build', '.git']);
        $consoleComposerJson = file_get_contents($this->upgradeInstancePath . '/typo3_console/composer.json');
        $consoleComposerJson = preg_replace('#"typo3/cms-([^"]*)": "([^"]*)"#', '"typo3/cms-\1": "\2 || ' . getenv('TYPO3_VERSION') . '"', $consoleComposerJson);
        file_put_contents($this->upgradeInstancePath . '/typo3_console/composer.json', $consoleComposerJson);
        $this->executeComposerCommand(['config', 'repositories.console', '{"type": "path", "url": "typo3_console", "options": {"symlink": false}}']);
        $output = $this->executeComposerCommand(['require', 'typo3/cms-core=' . getenv('TYPO3_UPGRADE_FROM_VERSION'), 'helhum/typo3-console=@dev']);
        $this->assertContains('Mirroring from typo3_console', $output);

        $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . $this->upgradeInstanceDatabase, false);
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--no-interaction',
                '--database-user-name' => getenv('TYPO3_INSTALL_DB_USER'),
                '--database-user-password' => getenv('TYPO3_INSTALL_DB_PASSWORD'),
                '--database-host-name' => getenv('TYPO3_INSTALL_DB_HOST'),
                '--database-port' => '3306',
                '--database-name' => $this->upgradeInstanceDatabase,
                '--admin-user-name' => 'admin',
                '--admin-password' => 'password',
                '--site-name' => 'Travis Install',
                '--site-setup-type' => 'createsite',
            ],
            [
                'TYPO3_PATH_COMPOSER_ROOT' => $this->upgradeInstancePath,
                'TYPO3_PATH_ROOT' => $this->upgradeInstancePath . '/public',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
    }

    /**
     * @param string $typo3Version
     */
    private function upgradeCodeToTypo3Version($typo3Version)
    {
        $this->executeComposerCommand(
            [
                'require',
                'typo3/cms-backend=' . $typo3Version,
                'typo3/cms-core=' . $typo3Version,
                'typo3/cms-extbase=' . $typo3Version,
                'typo3/cms-extensionmanager=' . $typo3Version,
                'typo3/cms-fluid=' . $typo3Version,
                'typo3/cms-frontend=' . $typo3Version,
                'typo3/cms-install=' . $typo3Version,
                'typo3/cms-recordlist=' . $typo3Version,
                'typo3/cms-saltedpasswords=*',
                'typo3/cms-scheduler=' . $typo3Version,
                '--no-update',
            ]
        );
        $output = $this->executeComposerCommand(['update']);
        if (DIRECTORY_SEPARATOR === '\\') {
            $output = preg_replace('/[^\x09-\x0d\x1b\x20-\xff]/', '', $output);
        }
        $this->assertContains('Generating autoload files', $output);
    }

    private function tearDownTypo3Instance()
    {
        chdir($this->consoleRootPath);
        try {
            $this->removeDirectory($this->upgradeInstancePath);
            $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . $this->upgradeInstanceDatabase, false);
        } catch (\Throwable $e) {
            // Ignore exceptions for tear down
        }
    }

    /**
     * @param array $arguments
     * @param array $environmentVariables
     * @param bool $dryRun
     * @return string
     */
    protected function executeComposerCommand(array $arguments = [], array $environmentVariables = [], $dryRun = false)
    {
        $environmentVariables['TYPO3_CONSOLE_SUB_PROCESS'] = 'yes';
        $commandLine = [];

        if (getenv('PHP_PATH')) {
            $commandLine[] = getenv('PHP_PATH');
        }
        $composerFinder = new ExecutableFinder();
        $composerBin = $composerFinder->find('composer');
        $commandLine[] = $composerBin;

        foreach ($arguments as $argument) {
            $commandLine[] = $argument;
        }
        $commandLine[] = '--no-ansi';
        $commandLine[] = '-d';
        $commandLine[] = $this->upgradeInstancePath;

        $process = new Process($commandLine, null, $environmentVariables, null, 0);
        if ($dryRun) {
            return $process->getCommandLine();
        }
        $process->run();
        if (!$process->isSuccessful()) {
            $this->fail(sprintf('Composer command "%s" failed with message: "%s", output: "%s"', $process->getCommandLine(), $process->getErrorOutput(), $process->getOutput()));
        }

        return $process->getOutput() . $process->getErrorOutput();
    }
}
