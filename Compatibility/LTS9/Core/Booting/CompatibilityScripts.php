<?php
namespace Helhum\Typo3Console\LTS9\Core\Booting;

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

use TYPO3\CMS\Core\Core\Bootstrap;

class CompatibilityScripts
{
    public static function initializeConfigurationManagement()
    {
        // noop for TYPO3 9
    }

    /**
     * @param Bootstrap $bootstrap
     * @deprecated can be removed when TYPO3 8 support is removed
     */
    public static function initializeDatabaseConnection(Bootstrap $bootstrap)
    {
        // noop for TYPO3 9
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeExtensionConfiguration(Bootstrap $bootstrap)
    {
        // noop for TYPO3 9
    }
}
