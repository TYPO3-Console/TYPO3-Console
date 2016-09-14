<?php
namespace Helhum\Typo3Console\Service\Database;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for database schema migrations
 */
class ImportService implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
     * @inject
     */
    protected $schemaMigrationService;

    /**
     * @var \Helhum\Typo3Console\Utility\InstallUtility
     * @inject
     */
    protected $installUtility;

    /**
     * @var \TYPO3\CMS\Core\Registry
     * @inject
     */
    protected $registry;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * Set console output class
     *
     * @param ConsoleOutput $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Import static SQL data (normally used for ext_tables_static+adt.sql)
     *
     * @param string $extensionKey Import static data for extension
     * @throws \Helhum\Typo3Console\Service\Database\Exception
     * @see \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::importStaticSql
     */
    public function importStaticSql($extensionKey)
    {
        $absoluteExtensionPath = ExtensionManagementUtility::extPath($extensionKey);
        $extensionRelativePath = str_replace(PATH_site, '', $absoluteExtensionPath);
        $staticSqlFile = 'ext_tables_static+adt.sql';
        $absolutePathToSqlFile = $absoluteExtensionPath . $staticSqlFile;
        $relativePathToSqlFile = $extensionRelativePath . $staticSqlFile;

        if (
            !file_exists($absolutePathToSqlFile) ||
            !is_readable($absolutePathToSqlFile) ||
            !$this->isTableUpdateNeeded($relativePathToSqlFile)
        ) {
            return;
        }

        // Force import of static database content
        $this->registry->set('extensionDataImport', $relativePathToSqlFile, 0);

        $this->outputFormatted('Importing static sql data for extension "%s"' . PHP_EOL, [$extensionKey]);
        $this->outputFormatted(GeneralUtility::getUrl($absolutePathToSqlFile), [], 2);

        $this->installUtility->importStaticSqlFile($extensionRelativePath);
    }

    /**
     * Write md5 value of $extTablesStaticSqlFile to TYPO3 registry.
     *
     * Called by signal "afterExtensionStaticSqlImport" from extension manager
     *
     * @param string $extTablesStaticSqlFile
     * @return void
     */
    public function writeMd5Value($extTablesStaticSqlFile)
    {
        $this->registry->set(
            'extensionDataImport',
            $extTablesStaticSqlFile,
            md5_file(PATH_site . $extTablesStaticSqlFile)
        );
    }

    /**
     * Formats the given text to fit into the maximum line length and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param int $leftPadding The number of spaces to use for indentation
     * @return void
     * @see outputLine()
     */
    protected function outputFormatted($text = '', array $arguments = array(), $leftPadding = 0)
    {
        if ($this->output instanceof ConsoleOutput) {
            $this->output->outputFormatted($text, $arguments, $leftPadding);
        }
    }

    /**
     * Check if table update is needed using md5 checksum for the content of "ext_tables_static+adt.sql" file.
     *
     * @param string $extTablesStaticSqlFile Path to extension static database content
     * @return bool
     */
    protected function isTableUpdateNeeded($extTablesStaticSqlFile)
    {
        $md5 = md5_file(PATH_site . $extTablesStaticSqlFile);
        return $this->registry->get('extensionDataImport', $extTablesStaticSqlFile) !== $md5;
    }
}
