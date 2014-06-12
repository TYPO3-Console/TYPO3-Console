<?php
namespace Helhum\Typo3Console\Install\Mcv;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Controller\StepController;

/**
 * Class Disptacher
 * @todo: this inheritance does not look nice, but in fact the step controller dispatches actions
 */
class Dispatcher extends StepController {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param string $actionName
	 * @param array $arguments
	 * @return mixed
	 */
	public function dispatchAction($actionName, $arguments = array()) {

		$classPrefix = 'TYPO3\\CMS\\Install\\Controller\\Action\\Step\\';
		$className = $classPrefix . ucfirst($actionName);

		/** @var ActionInterface $action */
		$action = $this->objectManager->get($className);
		$action->setController('step');
		$action->setAction($actionName);
		$action->setPostValues(array('values' => $arguments));
		try {
			$needsExecution = $action->needsExecution();
		} catch(\TYPO3\CMS\Install\Controller\Exception\RedirectException $e) {
			// todo: we may need to really reload everything from scratch here but for now we just proceed
			$needsExecution = TRUE;
		}

		if (file_exists(PATH_site . 'typo3conf/LocalConfiguration.php')) {
			$this->executeSilentConfigurationUpgradesIfNeeded();
		}

		if ($needsExecution) {
			$messages = $action->execute();
			$this->reloadConfiguration();
			return $messages;
		} else {
			return FALSE;
		}
	}

	/**
	 * Call silent upgrade class, redirect to self if configuration was changed.
	 *
	 * @return void
	 * @throws RedirectException
	 */
	protected function executeSilentConfigurationUpgradesIfNeeded() {
		/** @var \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService $upgradeService */
		$upgradeService = $this->objectManager->get(
			'TYPO3\\CMS\\Install\\Service\\SilentConfigurationUpgradeService'
		);

		$count = 0;
		do {
			try {
				$count++;
				$upgradeService->execute();
				$redirect = FALSE;
			} catch (RedirectException $e) {
				$redirect = TRUE;
				$this->reloadConfiguration();
				if ($count > 20) {
					throw $e;
				}
			}
		} while ($redirect === TRUE);
	}

	/**
	 * Fetch the new configuration and expose it to the global array
	 */
	protected function reloadConfiguration() {
		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManger */
		$configurationManger = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager')->get('\TYPO3\CMS\Core\Configuration\ConfigurationManager');
		$configurationManger->exportConfiguration();
	}
}