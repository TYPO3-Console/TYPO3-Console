<?php
declare(strict_types=1);
namespace Helhum\Typo3Console;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Compatibility code to support TYPO3 11 and 12
 */
class Typo3CompatibilityBridge
{
    public static function getSystemConfigurationFileLocation(): string
    {
        if ((new Typo3Version())->getMajorVersion() > 11) {
            return (new ConfigurationManager())->getSystemConfigurationFileLocation();
        }

        return (new ConfigurationManager())->getLocalConfigurationFileLocation();
    }
}
