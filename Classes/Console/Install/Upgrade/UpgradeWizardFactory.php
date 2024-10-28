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

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Creates a single upgrade wizard
 */
class UpgradeWizardFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $wizardRegistry;

    /**
     * @param ContainerInterface $container
     * @param array $wizardRegistry
     */
    public function __construct(
        ?ContainerInterface $container = null,
        ?array $wizardRegistry = null
    ) {
        $this->container = $container ?: new class() implements ContainerInterface {
            public function get(string $id)
            {
                return GeneralUtility::makeInstance($id);
            }

            public function has(string $id): bool
            {
                return true;
            }
        };
        $this->wizardRegistry = $wizardRegistry ?? $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] ?? [];
    }

    /**
     * Creates instance of an upgrade wizard
     *
     * @param string $identifier The identifier or class name of an upgrade wizard
     * @throws RuntimeException
     * @return UpgradeWizardInterface Newly instantiated upgrade wizard
     */
    public function create(string $identifier)
    {
        /** @var UpgradeWizardInterface $upgradeWizard */
        $upgradeWizard = $this->container->get($this->getClassNameFromIdentifier($identifier));
        if ($upgradeWizard instanceof ChattyInterface) {
            $output = new BufferedOutput();
            $upgradeWizard->setOutput($output);
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
