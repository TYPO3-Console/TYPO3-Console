<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Install\Upgrade;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
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

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ConfigurationManager $configurationManager = null, ObjectManagerInterface $objectManager = null)
    {
        $this->configurationManager = $configurationManager ?: GeneralUtility::makeInstance(ConfigurationManager::class);
        $this->objectManager = $objectManager ?: GeneralUtility::makeInstance(ObjectManager::class);
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
        $upgradeService = $this->objectManager->get(SilentConfigurationUpgradeService::class);
        $count = 0;
        do {
            try {
                $count++;
                $upgradeService->execute();
                $redirect = false;
            } catch (RedirectException $e) {
                $redirect = true;
                $this->configurationManager->exportConfiguration();
                if ($count > 20) {
                    throw new RuntimeException('Too many loops when silently upgrading configuration', 1493897404, $e);
                }
            }
        } while ($redirect === true);
    }
}
