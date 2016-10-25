<?php
namespace Helhum\Typo3Console\Database\Configuration;

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

/**
 * Class ConnectionConfiguration
 */
class ConnectionConfiguration
{
    /**
     * Returns a normalized DB configuration array
     *
     * @return array
     */
    public function build()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']) && is_array($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'])) {
            // We are on TYPO3 > 8.0
            $dbConfig = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        } else {
            // @deprecated with 8 will be removed when 7 Support is dropped
            $dbConfig = [
                'dbname' => TYPO3_db,
                'host' => TYPO3_db_host,
                'user' => TYPO3_db_username,
                'password' => TYPO3_db_password,
            ];
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['port'])) {
                $dbConfig['port'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['port'];
            }
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['socket'])) {
                $dbConfig['unix_socket'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['socket'];
            }
        }
        return $dbConfig;
    }
}
