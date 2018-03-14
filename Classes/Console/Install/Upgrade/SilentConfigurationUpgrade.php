<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Upgrade;

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

use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;

/**
 * Used to migrate deprecated LocalConfiguration.php values to new values
 * It is a wrapper around the TYPO3 class to properly handle redirect exceptions
 */
class SilentConfigurationUpgrade
{
    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    public function __construct(ConfigurationManager $configurationManager = null)
    {
        $this->configurationManager = $configurationManager ?: GeneralUtility::makeInstance(ConfigurationManager::class);
    }

    /**
     * Call silent upgrade class, redirect to self if configuration was changed.
     *
     * @throws \UnexpectedValueException
     * @throws RuntimeException
     */
    public function executeSilentConfigurationUpgradesIfNeeded()
    {
        if (!file_exists($this->configurationManager->getLocalConfigurationFileLocation())) {
            return;
        }
        // We need to write the extension configuration for all active extensions ourselves
        // as the core does not take care doing so (yet)
        $extensionConfigurationService = new ExtensionConfiguration();
        $extensionConfigurationService->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();

        $upgradeService = new SilentConfigurationUpgradeService($this->configurationManager);
        $count = 0;
        do {
            try {
                $count++;
                $upgradeService->execute();
                $redirect = false;
            } catch (ConfigurationChangedException $e) {
                $redirect = true;
                $this->configurationManager->exportConfiguration();
                if ($count > 20) {
                    throw new RuntimeException('Too many loops when silently upgrading configuration', 1493897404, $e);
                }
            }
        } while ($redirect === true);
    }
}
