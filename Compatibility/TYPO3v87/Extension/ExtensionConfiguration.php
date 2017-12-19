<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Extension;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * With the current limited concept of extension configuration,
 * essential TYPO3 Console functionality would be impossible,
 * such as extension setup from active extensions, or flushing low level caches.
 *
 * Therefore we replace the original class with a more graceful alternative.
 */
class ExtensionConfiguration
{
    /**
     * @var InstallUtility
     */
    private $extensionInstaller;

    public function __construct(InstallUtility $extensionInstaller = null)
    {
        $this->extensionInstaller = $extensionInstaller ?: GeneralUtility::makeInstance(ObjectManager::class)->get(InstallUtility::class);
    }

    public function saveDefaultConfiguration(string $extension, $force = false)
    {
        if ((!$force
            && isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extension]))
            || !@is_file(ExtensionManagementUtility::extPath($extension, 'ext_conf_template.txt'))
        ) {
            return;
        }
        $method = 'saveDefaultConfiguration';
        $installer = $this->extensionInstaller;
        \Closure::bind(function () use ($installer, $method, $extension) {
            return $installer->$method($extension);
        }, null, $installer)();
    }
}
