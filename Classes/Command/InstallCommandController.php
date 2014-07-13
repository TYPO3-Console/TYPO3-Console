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
use TYPO3\CMS\Install\Status\StatusInterface;

/**
 * Pre-alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Mvc\Cli\CommandManager
	 * @inject
	 */
	protected $commandManager;

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
		$this->outputLine();

		$this->dispatchAction('environmentAndFolders');
		$this->dispatchAction('databaseConnect');
		$this->dispatchAction('databaseSelect');
		$this->dispatchAction('databaseData');
		$this->dispatchAction('defaultConfiguration');

		$this->outputLine();
		$this->outputLine('Successfully installed TYPO3 CMS!');
}

	/**
	 * Check environment and create folder structure
	 *
	 * @internal
	 */
	public function environmentAndFoldersCommand() {
		touch(PATH_site . 'FIRST_INSTALL');
		$messages = $this->executeAction($this->createActionWithNameAndArguments('environmentAndFolders'));
		$this->outputLine(serialize($messages));
	}

	/**
	 * Database connection details
	 *
	 * @param string $databaseUserName User name for database server
	 * @param string $databaseUserPassword User password for database server
	 * @param string $databaseHostName Host name of database server
	 * @param string $databasePort TCP Port of database server
	 * @param string $databaseSocket Unix Socket to connect to
	 * @internal
	 */
	public function databaseConnectCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = 'localhost', $databasePort = '3306', $databaseSocket = '') {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('databaseConnect', array('host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket)));
		$this->outputLine(serialize($messages));
	}

	/**
	 * Select a database name
	 *
	 * @param string $databaseName Name of the database (will be created)
	 * @internal
	 */
	public function databaseSelectCommand($databaseName) {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('databaseSelect', array('type' => 'new', 'new' => $databaseName)));
		$this->outputLine(serialize($messages));
	}

	/**
	 * Admin user and site name
	 *
	 * @param string $adminUserName Username of your first admin user
	 * @param string $adminPassword Password of first admin user
	 * @param string $siteName Site name
	 * @internal
	 */
	public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site') {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('databaseData', array('username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName)));

		// TODO: ultimately get rid of that!
		/** @var DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		$db->exec_INSERTquery('be_users', array('username' => '_cli_lowlevel'));

		$this->outputLine(serialize($messages));
	}

	/**
	 * Write default configuration
	 *
	 * @internal
	 */
	public function defaultConfigurationCommand() {
		$messages = $this->executeAction($this->createActionWithNameAndArguments('defaultConfiguration'));
		$this->outputLine(serialize($messages));
	}



	// TODO: Refactor to different class

	/**
	 * @param string $actionName
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException
	 */
	protected function dispatchAction($actionName) {
		$this->commandMethodName = $actionName . 'Command';
		$this->initializeCommandMethodArguments();
		$command = $this->commandManager->getCommandByIdentifier('install:' . strtolower($actionName));

		do {
			$this->outputLine(sprintf('%s:', $command->getShortDescription()));

			$actionArguments = array();
			foreach ($command->getArgumentDefinitions() as $argumentDefinition) {
				$argument = $this->arguments->getArgument($argumentDefinition->getName());
				if ($this->request->hasArgument($argumentDefinition->getName())) {
					$actionArguments[$argumentDefinition->getName()] = $this->request->getArgument($argumentDefinition->getName());
				} else {
					$argumentValue = NULL;
					do {
						$argumentValue = $this->ask(
							sprintf(
								'<comment>%s (%s):</comment> ',
								$argumentDefinition->getDescription(),
								$argument->isRequired() ? 'required' : sprintf('default: "%s"', $argument->getDefaultValue())
							)
						);
					} while ($argumentDefinition->isRequired() && $argumentValue === NULL);
					$actionArguments[$argumentDefinition->getName()] = $argumentValue ?: $argument->getDefaultValue();
				}
			}

			$messages = $this->dispatcher->dispatchAction($actionName, $actionArguments);
			$this->outputMessages($messages);

		} while(!empty($messages));


	}

	/**
	 * @param StatusInterface[] $messages
	 */
	protected function outputMessages(array $messages = array()) {
		if (empty($messages)) {
			$this->outputLine('OK');
			return;
		}
		$this->outputLine();
		foreach ($messages as $statusMessage) {
			$this->outputStatusMessage($statusMessage);
		}
		$this->outputLine();
	}

	/**
	 * @param StatusInterface $statusMessage
	 */
	protected function outputStatusMessage(StatusInterface $statusMessage) {
		$subject = strtoupper($statusMessage->getSeverity()) . ': ' . $statusMessage->getTitle();
		switch ($statusMessage->getSeverity()) {
			case 'error':
				$subject = '<error>' . $subject . '</error>';
			break;
			default:
		}
		$this->outputLine($subject);
		$this->outputLine(wordwrap($statusMessage->getMessage()));
	}

	/**
	 * @param string $actionName
	 * @param array $arguments
	 * @return ActionInterface
	 */
	protected function createActionWithNameAndArguments($actionName, array $arguments = array()) {
		// TODO: boy this is ugly, but it seems surprisingly hard to allow empty arguments from the command line ^^
		foreach ($arguments as &$argumentValue) {
			if ($argumentValue === '__EMPTY') {
				$argumentValue = '';
			}
		}

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
		try {
			$needsExecution = $action->needsExecution();
		} catch(\TYPO3\CMS\Install\Controller\Exception\RedirectException $e) {
			return 'REDIRECT';
		}

		if ($needsExecution) {
			return $action->execute();
		} else {
			return array();
		}

	}
}