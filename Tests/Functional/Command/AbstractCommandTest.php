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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

abstract class AbstractCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CommandDispatcher
     */
    protected $commandDispatcher;

    protected function setUp()
    {
        if (!file_exists(getenv('TYPO3_PATH_ROOT') . '/index.php')
            && strpos(getenv('TYPO3_PATH_ROOT'), '.Build/Web') === false
        ) {
            throw new \RuntimeException('TYPO3_PATH_ROOT is not properly set!', 1493574402);
        }
        $this->commandDispatcher = CommandDispatcher::createFromTestRun();
    }

    /**
     * @param string $sql
     * @param bool $useDatabase
     * @return string
     */
    protected function executeMysqlQuery($sql, $useDatabase = true)
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix('mysql');
        $processBuilder->add('--skip-column-names');
        $processBuilder->add('-u');
        $processBuilder->add(getenv('TYPO3_INSTALL_DB_USER'));
        $processBuilder->add('--password=' . getenv('TYPO3_INSTALL_DB_PASSWORD'));
        if ($useDatabase) {
            $processBuilder->add(getenv('TYPO3_INSTALL_DB_DBNAME'));
        }
        $processBuilder->setInput($sql);

        $mysqlProcess = $processBuilder->getProcess();
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException(sprintf('Executing query "%s" failed. Did you set TYPO3_INSTALL_DB_* correctly? Is your database server running?', $sql), 1493634196);
        }
        return $mysqlProcess->getOutput();
    }

    protected function backupDatabase()
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix('mysqldump');
        $processBuilder->add('-u');
        $processBuilder->add(getenv('TYPO3_INSTALL_DB_USER'));
        $processBuilder->add('--password=' . getenv('TYPO3_INSTALL_DB_PASSWORD'));
        $processBuilder->add(getenv('TYPO3_INSTALL_DB_DBNAME'));

        $mysqlProcess = $processBuilder->getProcess();
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException('Backing up database failed', 1493634217);
        }
        file_put_contents(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . getenv('TYPO3_INSTALL_DB_DBNAME') . '.sql', $mysqlProcess->getOutput());
    }

    protected function restoreDatabase()
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix('mysql');
        $processBuilder->add('-u');
        $processBuilder->add(getenv('TYPO3_INSTALL_DB_USER'));
        $processBuilder->add('--password=' . getenv('TYPO3_INSTALL_DB_PASSWORD'));
        $processBuilder->add(getenv('TYPO3_INSTALL_DB_DBNAME'));
        $processBuilder->setInput(file_get_contents(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . getenv('TYPO3_INSTALL_DB_DBNAME') . '.sql'));

        $mysqlProcess = $processBuilder->getProcess();
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException('Restoring database failed', 1493634218);
        }
        unlink(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . getenv('TYPO3_INSTALL_DB_DBNAME') . '.sql');
    }

    /**
     * @param string $extensionKey
     */
    protected function installFixtureExtensionCode($extensionKey)
    {
        $sourcePath = dirname(__DIR__) . '/Fixtures/Extensions/' . $extensionKey;
        $targetPath = getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/' . $extensionKey;
        $this->copyDirectory($sourcePath, $targetPath);
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @param array $ignoredDirectories
     */
    protected function copyDirectory($sourcePath, $targetPath, array $ignoredDirectories = [])
    {
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
    protected function removeFixtureExtensionCode($extensionKey)
    {
        $this->removeDirectory(getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/' . $extensionKey);
    }

    /**
     * @param string $targetPath
     */
    protected function removeDirectory($targetPath)
    {
        $fileSystem = new Filesystem();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $fileSystem->remove($iterator);
        $fileSystem->remove($targetPath);
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
        $processBuilder->add(getenv('TYPO3_PATH_COMPOSER_ROOT'));

        $process = $processBuilder->setTimeout(null)->getProcess();
        $process->run();
        if (!$process->isSuccessful()) {
            $this->fail(sprintf('Composer command failed with message: "%s", output: "%s"', $process->getErrorOutput(), $process->getOutput()));
        }
        return $process->getOutput() . $process->getErrorOutput();
    }
}
