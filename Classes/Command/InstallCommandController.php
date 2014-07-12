<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;

/**
 * Pre-alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Install\Mcv\Dispatcher
	 * @inject
	 */
	protected $dispatcher;

	/**
	 * Pre-alpha version of a setup command. Use with care and at your own risk!
	 *
	 * @param string $databaseUserName
	 * @param string $databaseUserPassword
	 * @param string $databaseHostName
	 * @param string $databasePort
	 * @param string $databaseSocket
	 * @param string $databaseName
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 */
	public function setupCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '', $databasePort = '', $databaseSocket = '', $databaseName = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site') {
		$this->outputLine();
		$this->outputLine('<options=bold>Welcome to the console installer of TYPO3 CMS!</options=bold>');

		touch(PATH_site . 'FIRST_INSTALL');
		$this->dispatcher->dispatchAction('environmentAndFolders', $this->request->getArguments());

		$requiredArguments = array('adminUserName', 'adminPassword', 'databaseName', 'databaseUserName', 'databaseUserPassword', 'databaseHostName', 'databasePort');
		if (!$this->hasRequiredArguments($requiredArguments)) {
			$this->outputLine();
			$this->outputLine('Please specify some required settings for this installation:');
		}

		$requiredArguments = array('databaseUserName', 'databaseUserPassword', 'databaseHostName', 'databasePort');
		if (!$this->hasRequiredArguments($requiredArguments)) {
			$this->outputLine();
			$this->outputLine('<options=bold>Database settings:</options=bold>');
			$this->requestRequiredArguments($requiredArguments);
		}
		$this->dispatcher->dispatchAction('databaseConnect', $this->request->getArguments());

		$requiredArguments = array('databaseName');
		if (!$this->hasRequiredArguments($requiredArguments)) {
			$this->requestRequiredArguments($requiredArguments);
		}
		$this->dispatcher->dispatchAction('databaseSelect', $this->request->getArguments());

		$requiredArguments = array('adminUserName', 'adminPassword');
		if (!$this->hasRequiredArguments($requiredArguments)) {
			$this->outputLine();
			$this->outputLine('<options=bold>Backend user credentials:</options=bold>');
			$this->requestRequiredArguments($requiredArguments);
		}
		$this->dispatcher->dispatchAction('databaseData', $this->request->getArguments());
		$this->dispatcher->dispatchAction('defaultConfiguration', $this->request->getArguments());

		$this->outputLine();
		$this->outputLine('Successfully installed TYPO3 CMS!');
	}

	/**
	 * @internal
	 */
	public function environmentAndFoldersCommand() {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('environmentAndFolders'));
		$this->outputLine(json_encode($messages));
	}

	/**
	 * @param string $databaseUserName
	 * @param string $databaseUserPassword
	 * @param string $databaseHostName
	 * @param string $databasePort
	 * @param string $databaseSocket
	 * @internal
	 */
	public function databaseConnectCommand($databaseUserName, $databaseUserPassword, $databaseHostName, $databasePort, $databaseSocket = '') {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('databaseConnect', array('host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket)));
		$this->outputLine(json_encode($messages));
	}

	/**
	 * @param string $databaseName
	 * @internal
	 */
	public function databaseSelectCommand($databaseName) {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('databaseSelect', array('type' => 'new', 'new' => $databaseName)));
		$this->outputLine(json_encode($messages));
	}

	/**
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 * @internal
	 */
	public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site') {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('databaseData', array('username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName)));

		// TODO: ultimately get rid of that!
		/** @var DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		$db->exec_INSERTquery('be_users', array('username' => '_cli_lowlevel'));

		$this->outputLine(json_encode($messages));
	}

	/**
	 * @internal
	 */
	public function defaultConfigurationCommand() {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('defaultConfiguration'));
		$this->outputLine(json_encode($messages));
	}

	// TODO: Refactor the code below to dispatcher class

	/**
	 * @param array $requiredArguments
	 * @return bool
	 */
	protected function hasRequiredArguments(array $requiredArguments) {
		foreach ($requiredArguments as $argumentName) {
			if (!$this->request->hasArgument($argumentName)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * @param array $requiredArguments
	 */
	protected function requestRequiredArguments(array $requiredArguments) {
		$requestArguments = $this->request->getArguments();
		foreach ($requiredArguments as $argumentName) {
			if (!$this->request->hasArgument($argumentName)) {
				$argumentValue = NULL;
				while ($argumentValue === NULL) {
					$argumentValue = $this->ask(sprintf('<comment>%s:</comment> ', strtolower(substr(preg_replace('/([A-Z][a-z0-9]+)/', '$1 ', ucfirst($argumentName)), 0, -1))));
				}
				$requestArguments[$argumentName] = $argumentValue;
			}
		}
		$this->request->setArguments($requestArguments);
	}

	// TODO: Refactor the code below to own class

	/**
	 * @param string $actionName
	 * @param array $arguments
	 * @return ActionInterface
	 */
	protected function createActionWithNameAndArguments($actionName, array $arguments = array()) {
		$classPrefix = 'TYPO3\\CMS\\Install\\Controller\\Action\\Step\\';
		$className = $classPrefix . ucfirst($actionName);

		/** @var ActionInterface $action */
		$action = $this->objectManager->get($className);
		$action->setController('step');
		$action->setAction($actionName);
		$action->setPostValues(array('values' => $arguments));

		return $action;
	}

	/**
	 * @param ActionInterface $action
	 * @return bool|string
	 */
	protected function executeAction(ActionInterface $action) {
		$needsExecution = FALSE;
		try {
			$needsExecution = $action->needsExecution();
		} catch(\TYPO3\CMS\Install\Controller\Exception\RedirectException $e) {
			return 'REDIRECT';
		}

		if ($needsExecution) {
			return $action->execute();
		} else {
			return FALSE;
		}

	}
}