<?php
namespace Helhum\Typo3Console\Install;

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

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Status\StatusInterface;

/**
 * This class acts as facade for the install tool step actions.
 * It glues together the execution of these actions with the user interaction on the command line
 */
class CliSetupRequestHandler {

	const INSTALL_COMMAND_CONTROLLER_CLASS = 'Helhum\\Typo3Console\\Command\\InstallCommandController';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \Helhum\Typo3Console\Mvc\Cli\CommandManager
	 * @inject
	 */
	protected $commandManager;

	/**
	 * @var \Helhum\Typo3Console\Mvc\Cli\CommandDispatcher
	 * @inject
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 * @inject
	 */
	protected $reflectionService;

	/**
	 * @var array List of necessary installation steps. Order is important!
	 */
	protected $installationActions = array(
		'environmentAndFolders',
		'databaseConnect',
		'databaseSelect',
		'databaseData',
		'defaultConfiguration',
	);

	/**
	 * @var ConsoleOutput
	 */
	protected $output;

	/**
	 * @var DialogHelper
	 */
	protected $dialogHelper;

	/**
	 * @var array
	 */
	protected $givenRequestArguments = array();

	/**
	 * @var bool
	 */
	protected $interactiveSetup = TRUE;

	/**
	 *
	 */
	public function __construct() {
		$this->output = new ConsoleOutput();

	}

	/**
	 * @param bool $interactiveSetup
	 * @param array $givenRequestArguments
	 */
	public function setup($interactiveSetup, array $givenRequestArguments) {
		$this->interactiveSetup = $interactiveSetup;
		$this->givenRequestArguments = $givenRequestArguments;

		touch(PATH_site . 'FIRST_INSTALL');
		foreach ($this->installationActions as $actionName) {
			$this->dispatchAction($actionName);
		}
	}

	/**
	 * Executes the given action and outputs the result messages
	 *
	 * @param string $actionName
	 * @param array $arguments
	 */
	public function executeActionWithArguments($actionName, array $arguments = array()) {
		// TODO: provide pre- and post-execute signals?
		$messages = $this->executeAction($this->createActionWithNameAndArguments($actionName, $arguments));
		// TODO: ultimately get rid of that!
		if ($actionName === 'databaseData') {
			/** @var DatabaseConnection $db */
			$db = $GLOBALS['TYPO3_DB'];
			$db->exec_INSERTquery('be_users', array('username' => '_cli_lowlevel'));
		}
		$this->outputLine(serialize($messages));
	}

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

	/**
	 * @param string $actionName
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException
	 * @throws \RuntimeException
	 */
	protected function dispatchAction($actionName) {
		$this->executeSilentConfigurationUpgradesIfNeeded();

		$arguments = $this->getCommandMethodArguments($actionName . 'Command');
		$command = $this->commandManager->getCommandByIdentifier('install:' . strtolower($actionName));

		$loopCounter = 0;

		do {
			$loopCounter++;
			$this->outputLine();
			$this->outputLine(sprintf('%s:', $command->getShortDescription()));

			$actionArguments = array();
			foreach ($command->getArgumentDefinitions() as $argumentDefinition) {
				$argument = $arguments->getArgument($argumentDefinition->getName());
				if (isset($this->givenRequestArguments[$argumentDefinition->getName()])) {
					$actionArguments[$argumentDefinition->getName()] = $this->givenRequestArguments[$argumentDefinition->getName()];
				} else {
					if (!$this->interactiveSetup) {
						if ($argument->isRequired()) {
							throw new \RuntimeException(sprintf('Argument "%s" is not set, but is required and user interaction has been disabled!', $argument->getName()), 1405273316);
						} else {
							continue;
						}
					}
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

			do {
				$messages = @unserialize($this->dispatcher->executeCommand('install:' . strtolower($actionName), $actionArguments));
			} while($messages === 'REDIRECT');
			$messages = $messages ?: array();

			$this->outputMessages($messages);

			if ($loopCounter > 10) {
				throw new \RuntimeException('Tried to dispatch "' . $actionName . '" ' . $loopCounter . ' times.', 1405269518);
			}

		} while(!empty($messages));
	}

	/**
	 * Initializes the arguments array of this controller by creating an empty argument object for each of the
	 * method arguments found in the designated command method.
	 *
	 * @param string $commandMethodName
	 * @return Arguments
	 * @throws InvalidArgumentTypeException
	 */
	protected function getCommandMethodArguments($commandMethodName) {
		$arguments = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments');
		$methodParameters = $this->reflectionService->getMethodParameters(self::INSTALL_COMMAND_CONTROLLER_CLASS, $commandMethodName);
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) {
				throw new InvalidArgumentTypeException(sprintf('The argument type for parameter $%s of method %s->%s() could not be detected.', $parameterName, self::INSTALL_COMMAND_CONTROLLER_CLASS, $commandMethodName), 1306755296);
			}
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);
			$arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}

		return $arguments;
	}

	/**
	 * Call silent upgrade class, redirect to self if configuration was changed.
	 *
	 * @return void
	 * @throws RedirectException
	 */
	protected function executeSilentConfigurationUpgradesIfNeeded() {
		if (!file_exists(PATH_site . 'typo3conf/LocalConfiguration.php')) {
			return;
		}

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

	// Logging and output related stuff
	// TODO: Move to own class

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
	 * Outputs specified text to the console window and appends a line break
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 * @see output()
	 * @see outputLines()
	 */
	protected function outputLine($text = '', array $arguments = array()) {
		$this->output($text . PHP_EOL, $arguments);
	}

	/**
	 * Outputs specified text to the console window
	 * You can specify arguments that will be passed to the text via sprintf
	 * @see http://www.php.net/sprintf
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 */
	protected function output($text, array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		$this->output->write($text);
	}

	/**
	 * Asks a question to the user
	 *
	 * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
	 * @param string $default The default answer if none is given by the user
	 * @param array $autocomplete List of values to autocomplete. This only works if "stty" is installed
	 * @return string The user answer
	 * @throws \RuntimeException If there is no data to read in the input stream
	 */
	protected function ask($question, $default = NULL, array $autocomplete = NULL) {
		return $this->getDialogHelper()->ask($this->output, $question, $default, $autocomplete);
	}

	/**
	 * Returns or initializes the symfony/console DialogHelper
	 *
	 * @return DialogHelper
	 */
	protected function getDialogHelper() {
		if ($this->dialogHelper === NULL) {
			$this->dialogHelper = new DialogHelper();
			$helperSet = new HelperSet(array(new FormatterHelper()));
			$this->dialogHelper->setHelperSet($helperSet);
		}
		return $this->dialogHelper;
	}


}