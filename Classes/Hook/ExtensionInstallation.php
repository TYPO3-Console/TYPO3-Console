<?php
namespace Helhum\Typo3Console\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Markus Klein <klein.t3@reelworx.at>
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

use Helhum\Typo3Console\Composer\InstallerScripts;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Hook
 */
class ExtensionInstallation
{
    const EXTKEY = 'typo3_console';

    /**
     * Actions to take after extension has been installed
     *
     * @param string $keyOfInstalledExtension
     */
    public function afterInstallation($keyOfInstalledExtension)
    {
        if (self::EXTKEY !== $keyOfInstalledExtension) {
            return;
        }
        InstallerScripts::postInstallExtension();

        $this->createCliBeUser();
    }

    protected function createCliBeUser()
    {
        $db = $this->getDatabaseConnection();

        $where = 'username = ' . $db->fullQuoteStr('_cli_lowlevel', 'be_users') . ' AND admin = 0';

        $user = $db->exec_SELECTgetSingleRow('*', 'be_users', $where);
        if ($user) {
            if ($user['deleted'] || $user['disable']) {
                $data = array(
                    'be_users' => array(
                        $user['uid'] => array(
                            'deleted' => 0,
                            'disable' => 0
                        )
                    )
                );
                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
                $dataHandler->stripslashes_values = false;
                $dataHandler->start($data, array());
                $dataHandler->process_datamap();
            }
        } else {
            // Prepare necessary data for _cli_lowlevel user creation
            $password = uniqid('scheduler', true);
            if (SaltedPasswordsUtility::isUsageEnabled()) {
                $objInstanceSaltedPW = SaltFactory::getSaltingInstance();
                $password = $objInstanceSaltedPW->getHashedPassword($password);
            }
            $data = array(
                'be_users' => array(
                    'NEW' => array(
                        'username' => '_cli_lowlevel',
                        'password' => $password,
                        'pid' => 0
                    )
                )
            );
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $dataHandler->stripslashes_values = false;
            $dataHandler->start($data, array());
            $dataHandler->process_datamap();
            // Check if a new uid was indeed generated (i.e. a new record was created)
            // (counting DataHandler errors doesn't work as some failures don't report errors)
            $numberOfNewIDs = count($dataHandler->substNEWwithIDs);
            if ((int)$numberOfNewIDs !== 1) {
                InstallerScripts::addFlashMessage('Failed to create _cli_lowlevel BE user.', 'BE user creation failed', AbstractMessage::WARNING);
            }
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
