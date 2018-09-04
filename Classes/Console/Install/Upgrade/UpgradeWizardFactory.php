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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Install\Updates\AbstractUpdate;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Creates a single upgrade wizard
 */
class UpgradeWizardFactory
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var array
     */
    private $wizardRegistry;

    /**
     * @param ObjectManager $objectManager
     * @param array $wizardRegistry
     */
    public function __construct(
        ObjectManager $objectManager = null,
        array $wizardRegistry = []
    ) {
        // @deprecated Object Manager can be removed, once TYPO3 7.6 support is removed
        $this->objectManager = $objectManager ?: GeneralUtility::makeInstance(ObjectManager::class);
        $this->wizardRegistry = $wizardRegistry ?: $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'];
    }

    /**
     * Creates instance of an upgrade wizard
     *
     * @param string $identifier The identifier or class name of an upgrade wizard
     * @throws RuntimeException
     * @return AbstractUpdate|UpgradeWizardInterface Newly instantiated upgrade wizard
     */
    public function create(string $identifier)
    {
        /** @var AbstractUpdate|UpgradeWizardInterface $upgradeWizard */
        $upgradeWizard = $this->objectManager->get($this->getClassNameFromIdentifier($identifier));
        if ($upgradeWizard instanceof AbstractUpdate) {
            $upgradeWizard->setIdentifier($identifier);
        }

        return $upgradeWizard;
    }

    /**
     * @param string $identifier
     * @throws RuntimeException
     * @return string
     */
    public function getClassNameFromIdentifier(string $identifier): string
    {
        if (empty($className = $this->wizardRegistry[$identifier] ?? '')
            && empty($className = $this->wizardRegistry['TYPO3\\CMS\\Install\\Updates\\' . $identifier] ?? '')
            && !class_exists($className = $identifier)
        ) {
            throw new RuntimeException(sprintf('Upgrade wizard "%s" not found', $identifier), 1491914890);
        }

        return $className;
    }

    /**
     * @param string $classNameOrIdentifier
     * @throws RuntimeException
     * @return string
     */
    public function getShortIdentifier(string $classNameOrIdentifier): string
    {
        if (!empty($className = $this->wizardRegistry[$classNameOrIdentifier] ?? '')
            || !empty($className = $this->wizardRegistry['TYPO3\\CMS\\Install\\Updates\\' . $classNameOrIdentifier] ?? '')
        ) {
            $classNameOrIdentifier = $className;
        }
        if ($identifier = array_search($classNameOrIdentifier, $this->wizardRegistry, true)) {
            return str_replace('TYPO3\\CMS\\Install\\Updates\\', '', $identifier);
        }
        throw new RuntimeException(sprintf('Upgrade wizard "%s" not found', $classNameOrIdentifier), 1508495588);
    }
}
