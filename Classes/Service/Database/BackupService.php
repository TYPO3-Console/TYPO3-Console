<?php
namespace Helhum\Typo3Console\Service\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Thomas Bußmeyer <thomas.bussmeyer@publicispixelpark.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for database backup
 */
class BackupService implements SingletonInterface
{

    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

    /**
     * @var string
     */
    private $backupDirectory;

    /**
     * @var string
     */
    private $backupFilename;

    /**
     * @var array
     */
    private $databaseConnection;

    /**
     * @var string
     */
    private $mysqldumpCommandLine;


    /**
     * Calls 'system()' function, passing through all arguments unchanged.
     *
     * @return int The result code from system():  0 == success.
     */
    public function process() {
        system($this->mysqldumpCommandLine, $result_code);

        return $result_code;
    }

    /**
     * Builds and sets the mysqldump command line.
     *
     * @todo Check whether if it is a socket mysql connection.
     */
    public function setMysqldumpCommandLine() {
        if (!isset($this->databaseConnection)) {
            $this->setDatabaseConnection();
        }

        $exec = 'mysqldump';
        $exec .=
            sprintf(
                ' --user=%s --password=%s --host=%s %s',
                escapeshellarg($this->databaseConnection['username']),
                escapeshellarg($this->databaseConnection['password']),
                escapeshellarg($this->databaseConnection['host']),
                escapeshellarg($this->databaseConnection['database'])
            );

        $exec .= ' --no-autocommit --single-transaction --opt -Q';
        $exec .= ' --skip-extended-insert --order-by-primary';
        $exec .= ' > ' . $this->backupDirectory . '/' . $this->backupFilename;

        $this->mysqldumpCommandLine = $exec;
    }

    /**
     * Sets the filename for the database backup.
     */
    public function setBackupFilename() {
        if (!isset($this->databaseConnection)) {
            $this->setDatabaseConnection();
        }

        $this->backupFilename = sprintf(
            'dump-%s.sql',
            $this->databaseConnection['database']
        );
    }

    /**
     * Sets the directory to put the backup into.
     *
     * @param string $backupDirectory
     */
    public function setBackupDirectory($backupDirectory) {
        $backupDirectory = rtrim($backupDirectory, '/');
        if ($this->checkIfDirectoryExists($backupDirectory) && $this->checkIfDirectoryIsWritable($backupDirectory)) {
            $this->backupDirectory = $backupDirectory;
        }
    }

    /**
     * Sets the database connection of the current Typo3 instance.
     */
    private function setDatabaseConnection() {
        print_r($GLOBALS['TYPO3_DB']);
        print_r($GLOBALS['TYPO3_DB']->databaseHost);
        $this->databaseConnection = $this->configurationManager->getConfigurationValueByPath('DB');
    }

    /**
     * Checks if a file exists and if it is a directory.
     *
     * @param string $filename
     * @return bool
     * @throws \UnexpectedValueException
     */
    private function checkIfDirectoryExists($filename) {
        if (file_exists($filename) && is_dir($filename)) {
            return true;
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Directory "%s" does not exist.',
                $filename
            ));
        }
    }

    /**
     * Checks if a directory is writable.
     *
     * @param $filename
     * @return bool
     * @throws \UnexpectedValueException
     */
    private function checkIfDirectoryIsWritable($filename) {
        if (is_writable($filename)) {
            return true;
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Directory "%s" not writable.',
                $filename
            ));
        }
    }
}
