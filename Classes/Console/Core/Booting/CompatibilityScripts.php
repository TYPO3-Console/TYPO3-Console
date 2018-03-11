<?php
namespace Helhum\Typo3Console\Core\Booting;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

class CompatibilityScripts
{
    public static function initializeConfigurationManagement()
    {
        // noop for TYPO3 9
    }

    /**
     * @deprecated can be removed when TYPO3 8 support is removed
     */
    public static function initializeDatabaseConnection()
    {
        // noop for TYPO3 9
    }

    public static function initializeExtensionConfiguration()
    {
        // noop for TYPO3 9
    }
}
