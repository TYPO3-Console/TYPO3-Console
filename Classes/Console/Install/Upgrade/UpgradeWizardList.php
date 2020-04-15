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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

/**
 * Handle update wizards
 */
class UpgradeWizardList
{
    /**
     * @var UpgradeWizardFactory
     */
    private $factory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var array
     */
    private $wizardRegistry;

    /**
     * @var array
     */
    private $listCache = [];
    /**
     * @var UpgradeWizardsService
     */
    private $upgradeWizardsService;

    public function __construct(
        UpgradeWizardFactory $factory = null,
        Registry $registry = null,
        array $wizardRegistry = [],
        UpgradeWizardsService $upgradeWizardsService = null
    ) {
        $this->factory = $factory ?: new UpgradeWizardFactory();
        $this->registry = $registry ?: GeneralUtility::makeInstance(Registry::class);
        $this->wizardRegistry = $wizardRegistry ?: $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'];
        $this->upgradeWizardsService = $upgradeWizardsService ?? GeneralUtility::makeInstance(UpgradeWizardsService::class);
    }

    /**
     * List available upgrade wizards
     *
     * @param bool $includeDone
     * @return array
     */
    public function listWizards($includeDone = false)
    {
        if (empty($this->listCache)) {
            $availableUpgradeWizards = [];
            foreach ($this->wizardRegistry as $identifier => $className) {
                $updateObject = $this->factory->create($identifier);
                $shortIdentifier = $updateObject->getIdentifier();
                $availableUpgradeWizards[$shortIdentifier] = [
                    'className' => $className,
                    'title' => $updateObject->getTitle(),
                    'explanation' => $updateObject->getDescription(),
                    'done' => false,
                ];
                $markedAsDone = $this->upgradeWizardsService->isWizardDone($shortIdentifier);
                $wizardClaimsExecution = $updateObject->updateNecessary();
                if ($markedAsDone || !$wizardClaimsExecution) {
                    $availableUpgradeWizards[$shortIdentifier]['done'] = true;
                }
            }
            $this->listCache = $availableUpgradeWizards;
        }

        return array_filter(
            $this->listCache,
            function ($info) use ($includeDone) {
                return $includeDone || !$info['done'];
            }
        );
    }
}
