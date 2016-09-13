<?php
namespace Helhum\Typo3Console\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Md5 files service class
 *
 */
class Md5FilesHandler
{
    /**
     * Information holder for md5 checksum values
     *
     * @var array
     */
    protected $md5Information = array();

    /**
     * Directory path, the md5 files are stored in
     *
     * @var string
     */
    protected $staticImportPath = '';

    /**
     * TYPO3 Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param string $baseDirectory Directory to save and read md5 files
     */
    public function __construct($baseDirectory)
    {
        $this->setFilePathForMd5Files($baseDirectory);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Fetch md5 checksum from md5 file for the given extension
     *
     * @param string $extension Name of TYPO3 extension
     * @return string md5 value
     */
    public function getMd5($extension)
    {
        if (!isset($this->md5Information[$extension])) {
            $md5 = '';
            $md5FilePath = $this->filePathForMd5ExtensionFile($extension);
            if (file_exists($md5FilePath) && is_readable($md5FilePath)) {
                $md5 = GeneralUtility::getUrl($md5FilePath);
            }
            $this->md5Information[$extension] = $md5;
        }
        return $this->md5Information[$extension];
    }

    /**
     * Update md5 checksum in the appropriate md5 file for the given extension
     *
     * @param string $extension Name of extension
     * @param string $md5 New md5 value
     * @return void
     */
    public function update($extension, $md5)
    {
        // Load json information, if not available
        $this->getMd5($extension);
        // Update information
        $this->md5Information[$extension] = $md5;
        // Write changes to disk
        $this->writeFile($extension, $md5);
    }

    /**
     * Remove the existing md5 file for the given extension
     *
     * @param string $extension Name of extension
     * @return bool
     */
    public function removeFile($extension)
    {
        $result = false;
        $filePath = $this->filePathForMd5ExtensionFile($extension);
        if (file_exists($filePath)) {
            $result = unlink($filePath);
        }
        return $result;
    }

    /**
     * Write md5 checksum into md5 file for the given extension
     *
     * @param string $extension Name of extension
     * @param string $md5 New md5 value
     * @return void
     */
    protected function writeFile($extension, $md5)
    {
        $filePath = $this->filePathForMd5ExtensionFile($extension);
        $result = GeneralUtility::writeFile($filePath, $md5);
        if (!$result) {
            $this->logger->error('Can not write file "' . $filePath . '"');
        }
    }

    /**
     * Return the path to md5 file for the given extension
     *
     * @param string $extension Extension name
     * @return string Path to md5 file
     */
    protected function filePathForMd5ExtensionFile($extension)
    {
        return $this->staticImportPath . '/' . $extension . '.md5';
    }

    /**
     * Set the path for md5 files without trailing slash
     *
     * @param string $baseDirectory Directory to save and read md5 files
     * @return void
     */
    protected function setFilePathForMd5Files($baseDirectory)
    {
        // Fallback, if no path configured.
        $filePath = PATH_site . 'typo3temp/';
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_console']['sharedPath']) &&
            is_dir($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_console']['sharedPath']) &&
            is_writable($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_console']['sharedPath'])
        ) {
            $filePath = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_console']['sharedPath'];
        }

        // Append static import directory
        $filePath = rtrim($filePath, '/') . '/' . rtrim($baseDirectory, '/');

        // Create directory, if not exists
        if (!is_dir($filePath)) {
            $result = GeneralUtility::mkdir($filePath);
            if (!$result) {
                $this->logger->error('Can not create directory "' . $filePath . '"');
            }
        }

        $this->staticImportPath = $filePath;
    }
}
