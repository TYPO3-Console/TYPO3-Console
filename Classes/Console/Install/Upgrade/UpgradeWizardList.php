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
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;

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
     * @param bool $includeRowUpdaters
     * @return array
     */
    public function listWizards($includeDone = false, bool $includeRowUpdaters = false): array
    {
        if (empty($this->listCache)) {
            $availableUpgradeWizards = [];
            foreach ($this->wizardRegistry as $identifier => $className) {
                $updateObject = $this->factory->create($identifier);
                $shortIdentifier = $updateObject->getIdentifier();
                $availableUpgradeWizards[$shortIdentifier] = [
                    'wizard' => $updateObject,
                    'className' => $className,
                    'title' => $updateObject->getTitle(),
                    'explanation' => $updateObject->getDescription(),
                    'confirmable' => false,
                    'done' => false,
                ];
                if ($includeRowUpdaters && $updateObject instanceof DatabaseRowsUpdateWizard) {
                    $availableUpgradeWizards = $this->extractRowUpdaters($updateObject, $availableUpgradeWizards);
                }
                if ($updateObject instanceof ConfirmableInterface) {
                    $confirmation = $updateObject->getConfirmation();
                    $availableUpgradeWizards[$shortIdentifier]['confirmable'] = true;
                    $availableUpgradeWizards[$shortIdentifier]['confirmation'] = [
                        'title' => $confirmation->getTitle(),
                        'message' => $confirmation->getMessage(),
                        'default' => $confirmation->getDefaultValue() ? 'allow' : 'deny',
                        'isRequired' => $confirmation->isRequired(),
                    ];
                }
                // TYPO3 is inconsistent here and does not really use the identifier of the wizard itself for this API, but the one in the configuration
                // This workaround was removed and is now added again to be kept to avoid further disturbance in this area
                $markedAsDone = $this->upgradeWizardsService->isWizardDone($identifier);
                if ($markedAsDone || !$updateObject->updateNecessary()) {
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

    private function extractRowUpdaters(DatabaseRowsUpdateWizard $rowsUpdateWizard, array $availableUpgradeWizards): array
    {
        $protectedProperty = 'rowUpdater';
        $availableRowUpdaters = \Closure::bind(function () use ($rowsUpdateWizard, $protectedProperty) {
            return $rowsUpdateWizard->$protectedProperty;
        }, null, $rowsUpdateWizard)();
        foreach ($this->upgradeWizardsService->listOfRowUpdatersDone() as $rowUpdatersDone) {
            $availableUpgradeWizards[$rowUpdatersDone['class']] = [
                'className' => $rowUpdatersDone['class'],
                'title' => $rowUpdatersDone['title'],
                'explanation' => 'rowUpdater',
                'done' => true,
            ];
        }
        $notDoneRowUpdaters = array_diff($availableRowUpdaters, array_keys($availableUpgradeWizards));
        foreach ($notDoneRowUpdaters as $notDoneRowUpdater) {
            $rowUpdater = GeneralUtility::makeInstance($notDoneRowUpdater);
            $availableUpgradeWizards[$notDoneRowUpdater] = [
                'className' => $notDoneRowUpdater,
                'title' => $rowUpdater->getTitle(),
                'explanation' => 'rowUpdater',
                'done' => false,
            ];
        }

        return $availableUpgradeWizards;
    }
}
