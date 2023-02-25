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

use Helhum\Typo3Console\Error\ExceptionRenderer;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractCommandTest extends TestCase
{
    /**
     * @var CommandDispatcher
     */
    protected $commandDispatcher;

    protected function setUp(): void
    {
        if (!file_exists(getenv('TYPO3_PATH_ROOT') . '/index.php')
            && strpos(getenv('TYPO3_PATH_ROOT'), '/public') === false
        ) {
            throw new \RuntimeException('TYPO3_PATH_ROOT is not properly set!', 1493574402);
        }
        putenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS=core');
        $_ENV['TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS'] = 'core';
        $this->commandDispatcher = CommandDispatcher::createFromTestRun();
    }

    /**
     * @param array $arguments
     * @param array $environmentVariables
     * @param bool $dryRun
     * @return string
     */
    protected static function executeComposerCommand(array $arguments = [], array $environmentVariables = [], $dryRun = false): string
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
        $commandLine[] = getenv('TYPO3_PATH_COMPOSER_ROOT');

        $process = new Process($commandLine, null, $environmentVariables, null, 0);
        if ($dryRun) {
            return $process->getCommandLine();
        }
        $process->run();
        if (!$process->isSuccessful()) {
            self::fail(sprintf('Composer command "%s" failed with message: "%s", output: "%s"', $process->getCommandLine(), $process->getErrorOutput(), $process->getOutput()));
        }

        return $process->getOutput() . $process->getErrorOutput();
    }

    /**
     * @param string $sql
     * @param bool $useDatabase
     * @return string
     */
    protected function executeMysqlQuery($sql, $useDatabase = true)
    {
        $arguments = [];
        if (!$useDatabase) {
            $arguments[] = '--no-db';
        }

        return $this->executeConsoleCommand('sql', $arguments, [], $sql);
    }

    protected function backupDatabase()
    {
        $this->skipOnSqlite();
        $commandLine = [
            'mysqldump',
            '-u',
            getenv('TYPO3_INSTALL_DB_USER'),
            '-h',
            getenv('TYPO3_INSTALL_DB_HOST'),
            '--password=' . getenv('TYPO3_INSTALL_DB_PASSWORD'),
            getenv('TYPO3_INSTALL_DB_DBNAME'),
        ];

        $mysqlProcess = new Process($commandLine, null, null, null, 0);
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException('Backing up database failed', 1493634217);
        }
        file_put_contents(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . getenv('TYPO3_INSTALL_DB_DBNAME') . '.sql', $mysqlProcess->getOutput());
    }

    protected function restoreDatabase()
    {
        $this->skipOnSqlite();
        $commandLine = [
            'mysql',
            '--skip-column-names',
            '-u',
            getenv('TYPO3_INSTALL_DB_USER'),
            '-h',
            getenv('TYPO3_INSTALL_DB_HOST'),
            '--password=' . getenv('TYPO3_INSTALL_DB_PASSWORD'),
            getenv('TYPO3_INSTALL_DB_DBNAME'),
        ];
        $sql = file_get_contents(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . getenv('TYPO3_INSTALL_DB_DBNAME') . '.sql');
        $mysqlProcess = new Process($commandLine, null, null, $sql, 0);
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException('Restoring database failed', 1493634218);
        }
        unlink(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . getenv('TYPO3_INSTALL_DB_DBNAME') . '.sql');
    }

    /**
     * @param string $extensionKey
     */
    protected static function installFixtureExtensionCode($extensionKey)
    {
        $sourcePath = dirname(__DIR__) . '/Fixtures/Extensions/' . $extensionKey;
        $targetPath = getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/' . $extensionKey;
        self::copyDirectory($sourcePath, $targetPath);
        GeneralUtility::rmdir(getenv('TYPO3_PATH_APP') . '/var/cache', true);
        self::executeComposerCommand(['du']);
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @param array $ignoredDirectories
     */
    protected static function copyDirectory($sourcePath, $targetPath, array $ignoredDirectories = [])
    {
        $ignoredDirectories = array_merge($ignoredDirectories, ['.git']);
        $fileSystem = new Filesystem();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            foreach ($ignoredDirectories as $ignoredDirectory) {
                if (strpos($item->getPathname(), $ignoredDirectory) !== false) {
                    continue 2;
                }
            }
            $target = $targetPath . '/' . $iterator->getSubPathName();
            if ($item->isDir()) {
                $fileSystem->mkdir($target);
            } else {
                $fileSystem->copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * @param string $extensionKey
     */
    protected static function removeFixtureExtensionCode($extensionKey)
    {
        self::removeDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/' . $extensionKey);
        GeneralUtility::rmdir(getenv('TYPO3_PATH_APP') . '/var/cache', true);
        self::executeComposerCommand(['du']);
    }

    /**
     * @param string $targetPath
     */
    protected static function removeDirectory($targetPath)
    {
        $fileSystem = new Filesystem();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $fileSystem->remove($iterator);
        $fileSystem->remove($targetPath);
    }

    protected function executeConsoleCommand($command, array $arguments = [], array $environment = [], string $stdIn = null)
    {
        try {
            return $this->commandDispatcher->executeCommand($command, $arguments, $environment, $stdIn);
        } catch (FailedSubProcessCommandException $e) {
            $exceptionRenderer = new ExceptionRenderer();
            $output = new BufferedOutput(BufferedOutput::VERBOSITY_DEBUG);
            $exceptionRenderer->render($e, $output);
            $this->fail($output->fetch());
        }

        return '';
    }

    protected function skipOnSqlite(): void
    {
        if ($this->runsOnSqlite()) {
            $this->markTestSkipped('This test can not be run on sqlite databases');
        }
    }

    protected function runsOnSqlite(): bool
    {
        return getenv('TYPO3_INSTALL_DB_DRIVER') === 'pdo_sqlite';
    }
}
