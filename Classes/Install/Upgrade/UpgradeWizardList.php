<?php
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
     * UpgradeWizardList constructor.
     *
     * @param UpgradeWizardFactory|null $factory
     * @param Registry|null $registry
     * @param array $wizardRegistry
     */
    public function __construct(
        UpgradeWizardFactory $factory = null,
        Registry $registry = null,
        array $wizardRegistry = []
    ) {
        $this->factory = $factory ?: new UpgradeWizardFactory();
        $this->registry = $registry ?: GeneralUtility::makeInstance(Registry::class);
        $this->wizardRegistry = $wizardRegistry ?: $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'];
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
                $availableUpgradeWizards[$identifier] = [
                    'title' => $updateObject->getTitle(),
                    'done' => false,
                ];
                $explanation = '';
                if ($this->registry->get('installUpdate', $className, false)
                    || !$updateObject->checkForUpdate($explanation)
                ) {
                    $availableUpgradeWizards[$identifier]['done'] = true;
                }
                $availableUpgradeWizards[$identifier]['explanation'] = html_entity_decode(strip_tags($explanation));
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
