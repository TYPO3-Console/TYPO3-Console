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
     * @return array
     */
    protected function getDatabaseConnectionSettings()
    {
        return [
            'dbUser' => getenv('TYPO3_INSTALL_DB_USER') ?: 'root',
            'dbPassword' => getenv('TYPO3_INSTALL_DB_PASSWORD') ?: '',
            'dbName' => getenv('TYPO3_INSTALL_DB_DBNAME') ?: 'travis_console_test',
        ];
    }

    /**
     * @param string $sql
     * @param bool $useDatabase
     * @return string
     */
    protected function executeMysqlQuery($sql, $useDatabase = true)
    {
        $settings = $this->getDatabaseConnectionSettings();
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix('mysql');
        $processBuilder->add('--skip-column-names');
        $processBuilder->add('-u');
        $processBuilder->add($settings['dbUser']);
        $processBuilder->add('--password=' . $settings['dbPassword']);
        if ($useDatabase) {
            $processBuilder->add($settings['dbName']);
        }
        $processBuilder->setInput($sql);

        $mysqlProcess = $processBuilder->getProcess();
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException(sprintf('Executing query "%s" failed', $sql), 1493634196);
        }
        return $mysqlProcess->getOutput();
    }

    protected function backupDatabase()
    {
        $settings = $this->getDatabaseConnectionSettings();
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix('mysqldump');
        $processBuilder->add('-u');
        $processBuilder->add($settings['dbUser']);
        $processBuilder->add('--password=' . $settings['dbPassword']);
        $processBuilder->add($settings['dbName']);

        $mysqlProcess = $processBuilder->getProcess();
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException('Backing up database failed', 1493634217);
        }
        file_put_contents(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . $settings['dbName'] . '.sql', $mysqlProcess->getOutput());
    }

    protected function restoreDatabase()
    {
        $settings = $this->getDatabaseConnectionSettings();
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix('mysql');
        $processBuilder->add('-u');
        $processBuilder->add($settings['dbUser']);
        $processBuilder->add('--password=' . $settings['dbPassword']);
        $processBuilder->add($settings['dbName']);
        $processBuilder->setInput(file_get_contents(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . $settings['dbName'] . '.sql'));

        $mysqlProcess = $processBuilder->getProcess();
        $mysqlProcess->run();
        if (!$mysqlProcess->isSuccessful()) {
            throw new \RuntimeException('Restoring database failed', 1493634218);
        }
        unlink(getenv('TYPO3_PATH_ROOT') . '/typo3temp/' . $settings['dbName'] . '.sql');
    }

    /**
     * @param string $extensionKey
     */
    protected function installFixtureExtensionCode($extensionKey)
    {
        $sourcePath = dirname(__DIR__) . '/Fixtures/' . $extensionKey;
        $targetPath = getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/' . $extensionKey;
        $this->copyDirectory($sourcePath, $targetPath);
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     */
    protected function copyDirectory($sourcePath, $targetPath)
    {
        $fileSystem = new Filesystem();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
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
}
