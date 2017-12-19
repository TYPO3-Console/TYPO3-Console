<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Extension;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ExtensionConfigurationService;

/**
 * With the current limited concept of extension configuration,
 * essential TYPO3 Console functionality would be impossible,
 * such as extension setup from active extensions, or flushing low level caches.
 *
 * Therefore we replace the original class with a more graceful alternative.
 */
class ExtensionConfiguration
{
    private static $isInFallbackMode = false;

    public function get(string $extension, string $path = '')
    {
        try {
            return $this->getConfiguration($extension, $path);
        } catch (\Throwable $e) {
            if (self::$isInFallbackMode) {
                throw $e;
            }
            self::$isInFallbackMode = true;
            $this->saveDefaultConfiguration($extension, true);
            self::$isInFallbackMode = false;
            try {
                return ArrayUtility::getValueByPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension], $path);
            } catch (\Throwable $e) {
                return $this->getConfiguration($extension, $path);
            }
        }
    }

    public function set(string $extension, string $path = '', $value = null)
    {
        if (self::$isInFallbackMode) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension] = $value;
        } else {
            $this->setConfiguration($extension, $path, $value);
        }
    }

    public function saveDefaultConfiguration(string $extension, $force = false)
    {
        if (!$force
            && isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extension], $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])
        ) {
            return;
        }
        $extensionConfigurationService = new ExtensionConfigurationService();
        $extensionConfigurationService->synchronizeExtConfTemplateWithLocalConfiguration($extension);
    }

    // Unmodified TYPO3 code below

    /**
     * API to get() and set() instance specific extension configuration options.
     *
     * Extension authors are encouraged to use this API - it is currently a simple
     * wrapper to access TYPO3_CONF_VARS['EXTENSIONS'] but could later become something
     * different in case core decides to store extension configuration elsewhere.
     *
     * Extension authors must not access TYPO3_CONF_VARS['EXTENSIONS'] on their own.
     *
     * Extension configurations are often 'feature flags' currently defined by
     * ext_conf_template.txt files. The core (more specifically the install tool)
     * takes care default values and overridden values are properly prepared upon
     * loading or updating an extension.
     */
    private function getConfiguration(string $extension, string $path = '')
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])) {
            throw new ExtensionConfigurationExtensionNotConfiguredException(
                'No extension configuration for extension ' . $extension . ' found. Either this extension'
                . ' has no extension configuration or the configuration is not up to date. Execute the'
                . ' install tool to update configuration.',
                1509654728
            );
        }
        if (empty($path)) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension];
        }
        if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path)) {
            throw new ExtensionConfigurationPathDoesNotExistException(
                'Path ' . $path . ' does not exist in extension configuration',
                1509977699
            );
        }
        return ArrayUtility::getValueByPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path);
    }

    private function setConfiguration(string $extension, string $path = '', $value = null)
    {
        if (empty($extension)) {
            throw new \RuntimeException('extension name must not be empty', 1509715852);
        }
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if ($path === '' && $value === null) {
            // Remove whole extension config
            $configurationManager->removeLocalConfigurationKeysByPath([ 'EXTENSIONS/' . $extension ]);
        } elseif ($path !== '' && $value === null) {
            // Remove a single value or sub path
            $configurationManager->removeLocalConfigurationKeysByPath([ 'EXTENSIONS/' . $extension . '/' . $path]);
        } elseif ($path === '' && $value !== null) {
            // Set full extension config
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extension, $value);
        } else {
            // Set single path
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extension . '/' . $path, $value);
        }

        // After TYPO3_CONF_VARS['EXTENSIONS'] has been written, update legacy layer TYPO3_CONF_VARS['EXTENSIONS']['extConf']
        // @deprecated since TYPO3 v9, will be removed in v10 with removal of old serialized 'extConf' layer
        $extensionsConfigs = $configurationManager->getConfigurationValueByPath('EXTENSIONS');
        foreach ($extensionsConfigs as $extensionName => $extensionConfig) {
            $extensionConfig = $this->addDotsToArrayKeysRecursiveForLegacyExtConf($extensionConfig);
            $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $extensionName, serialize($extensionConfig));
        }
    }

    private function addDotsToArrayKeysRecursiveForLegacyExtConf(array $extensionConfig)
    {
        $newArray = [];
        foreach ($extensionConfig as $key => $value) {
            if (is_array($value)) {
                $newArray[$key . '.'] = $this->addDotsToArrayKeysRecursiveForLegacyExtConf($value);
            } else {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }
}
