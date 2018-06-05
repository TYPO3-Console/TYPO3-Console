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
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

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
        $this->executeConsoleCommand('extension:activate', ['--extension-keys' => 'ext_test']);
        try {
            $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--typo3-version' => '3.6.0']);
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
            $this->assertContains('"ext_test" requires TYPO3 versions 4.5.0', $e->getOutputMessage());
        }
        $this->executeConsoleCommand('extension:deactivate', ['--extension-keys' => 'ext_test']);
        $this->removeFixtureExtensionCode('ext_test');
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsIssuesWarningForInvalidExtensionKeys()
    {
        $output = $this->executeConsoleCommand('upgrade:checkextensionconstraints', ['--extension-keys' => 'foo,bar']);
        $this->assertContains('Extension "foo" is not found in the system', $output);
        $this->assertContains('Extension "bar" is not found in the system', $output);
    }

    /**
     * @test
     */
    public function canPerformTypo3Upgrade()
    {
        $this->upgradeInstancePath = dirname(dirname(dirname(__DIR__))) . '/.Build/upgrade_test';
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
        $this->consoleRootPath = getenv('TYPO3_PATH_COMPOSER_ROOT');
        $this->typo3RootPath = getenv('TYPO3_PATH_ROOT');
        $this->commandDispatcher = CommandDispatcher::createFromTestRun($this->upgradeInstancePath . '/vendor/helhum/typo3-console-test/Scripts/typo3cms');
        if (!is_dir($this->upgradeInstancePath)) {
            mkdir($this->upgradeInstancePath);
        }
        chdir($this->upgradeInstancePath);

        file_put_contents($this->upgradeInstancePath . '/composer.json', '{}');
        $this->executeComposerCommand(['config', 'extra.typo3/cms.cms-package-dir', '{$vendor-dir}/typo3/cms']);
        $this->executeComposerCommand(['config', 'extra.typo3/cms.web-dir', 'web']);
        $this->executeComposerCommand(['config', 'extra.helhum/typo3-console.install-extension-dummy', '0']);
        $this->copyDirectory($this->consoleRootPath, $this->upgradeInstancePath . '/typo3_console', ['.Build', '.git']);
        $consoleComposerJson = file_get_contents($this->upgradeInstancePath . '/typo3_console/composer.json');
        $consoleComposerJson = str_replace('"name": "helhum/typo3-console"', '"name": "helhum/typo3-console-test"', $consoleComposerJson);
        file_put_contents($this->upgradeInstancePath . '/typo3_console/composer.json', $consoleComposerJson);
        $this->executeComposerCommand(['config', 'repositories.console', '{"type": "path", "url": "typo3_console", "options": {"symlink": false}}']);
        $output = $this->executeComposerCommand(['require', 'typo3/cms=^7.6.18', 'helhum/typo3-console-test=@dev']);
        $this->assertContains('Mirroring from typo3_console', $output);
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->assertContains('Copied typo3 directory to document root', $output);
        }

        $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . $this->upgradeInstanceDatabase, false);
        $output = $this->executeConsoleCommand(
            'install:setup',
            [
                '--non-interactive' => true,
                '--database-user-name' => getenv('TYPO3_INSTALL_DB_USER'),
                '--database-user-password' => getenv('TYPO3_INSTALL_DB_PASSWORD'),
                '--database-host-name' => 'localhost',
                '--database-port' => '3306',
                '--database-name' => $this->upgradeInstanceDatabase,
                '--admin-user-name' => 'admin',
                '--admin-password' => 'password',
                '--site-name' => 'Travis Install',
                '--site-setup-type' => 'createsite',
            ],
            [
                'TYPO3_PATH_COMPOSER_ROOT' => $this->upgradeInstancePath,
                'TYPO3_PATH_ROOT' => $this->upgradeInstancePath . '/web',
                'TYPO3_PATH_WEB' => $this->upgradeInstancePath . '/web',
            ]
        );
        $this->assertContains('Successfully installed TYPO3 CMS!', $output);
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

    private function tearDownTypo3Instance()
    {
        chdir($this->consoleRootPath);
        try {
            $this->removeDirectory($this->upgradeInstancePath);
            $this->executeMysqlQuery('DROP DATABASE IF EXISTS ' . $this->upgradeInstanceDatabase, false);
        } catch (\Exception $e) {
            // Ignore this exception on Windows
            if (DIRECTORY_SEPARATOR !== '\\') {
                throw $e;
            }
        }
    }

    /**
     * @param array $arguments
     * @param array $environmentVariables
     * @return string
     */
    protected function executeComposerCommand(array $arguments = [], array $environmentVariables = [])
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->addEnvironmentVariables($environmentVariables);
        $processBuilder->setEnv('TYPO3_CONSOLE_SUB_PROCESS', 'yes');

        if ($phpPath = getenv('PHP_PATH')) {
            $phpFinder = new PhpExecutableFinder();
            $processBuilder->setPrefix($phpFinder->find(false));
            $processBuilder->add($phpPath . '/composer.phar');
        } else {
            $processBuilder->setPrefix('composer');
        }
        foreach ($arguments as $argument) {
            $processBuilder->add($argument);
        }
        $processBuilder->add('--no-ansi');
        $processBuilder->add('-d');
        $processBuilder->add($this->upgradeInstancePath);

        $process = $processBuilder->setTimeout(null)->getProcess();
        $process->run();
        if (!$process->isSuccessful()) {
            $this->fail(sprintf('Composer command "%s" failed with message: "%s", output: "%s"', $process->getCommandLine(), $process->getErrorOutput(), $process->getOutput()));
        }
        return $process->getOutput() . $process->getErrorOutput();
    }
}
