<?php
namespace Helhum\Typo3Console\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Helhum\Typo3Console\Log\Writer\ConsoleWriter;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Extbase\Mvc\Cli\Request;
use TYPO3\CMS\Extbase\Mvc\Cli\Response;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandControllerInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\CommandException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;

/**
 * A controller which processes requests from the command line
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CommandController implements CommandControllerInterface {

	/**
	 * The maximum length a line should be before it's wrapped when using outputFormatted()
	 *
	 * @var integer
	 */
	const MAXIMUM_LINE_LENGTH = 79;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var Arguments
	 */
	protected $arguments;

	/**
	 * @var ConsoleOutput
	 */
	protected $output;

	/**
	 * @var DialogHelper
	 */
	protected $dialogHelper;

	/**
	 * @var ProgressHelper
	 */
	protected $progressHelper;

	/**
	 * @var TableHelper
	 */
	protected $tableHelper;

	/**
	 * Name of the command method
	 *
	 * @var string
	 */
	protected $commandMethodName = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 * @inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->arguments = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments');
		$this->output = new ConsoleOutput();
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @api
	 */
	public function canProcessRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request) {
		return $request instanceof Request;
	}

	/**
	 * Processes a command line request.
	 *
	 * @param RequestInterface $request The request object
	 * @param ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @throws UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @api
	 */
	public function processRequest(RequestInterface $request, ResponseInterface $response) {
		if (!$request instanceof Request) {
			throw new UnsupportedRequestTypeException(sprintf('%s only supports command line requests – requests of type "%s" given.', get_class($this), get_class($request)), 1300787096);
		}

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->commandMethodName = $this->resolveCommandMethodName();
		$this->initializeCommandMethodArguments();
		$this->mapRequestArgumentsToControllerArguments();
		$this->callCommandMethod();
	}

	/**
	 * Resolves and checks the current command method name
	 *
	 * Note: The resulting command method name might not have the correct case, which isn't a problem because PHP is case insensitive regarding method names.
	 *
	 * @return string Method name of the current command
	 * @throws NoSuchCommandException
	 */
	protected function resolveCommandMethodName() {
		$commandMethodName = $this->request->getControllerCommandName() . 'Command';
		if (!is_callable(array($this, $commandMethodName))) {
			throw new NoSuchCommandException(sprintf('A command method "%s()" does not exist in controller "%s".', $commandMethodName, get_class($this)), 1300902143);
		}
		return $commandMethodName;
	}

	/**
	 * Initializes the arguments array of this controller by creating an empty argument object for each of the
	 * method arguments found in the designated command method.
	 *
	 * @return void
	 * @throws InvalidArgumentTypeException
	 */
	protected function initializeCommandMethodArguments() {
		$this->arguments->removeAll();
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->commandMethodName);

		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) {
				throw new InvalidArgumentTypeException(sprintf('The argument type for parameter $%s of method %s->%s() could not be detected.', $parameterName, get_class($this), $this->commandMethodName), 1306755296);
			}
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);
			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		/** @var Argument $argument */
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();

			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
				continue;
			}
			if (!$argument->isRequired()) {
				continue;
		}
			$argumentValue = NULL;
			while ($argumentValue === NULL) {
				$argumentValue = $this->ask(sprintf('<comment>Please specify the required argument "%s":</comment> ', $argumentName));
			}

			if ($argumentValue === NULL) {
				$exception = new CommandException(sprintf('Required argument "%s" is not set.', $argumentName), 1306755520);
				$this->forward('error', 'TYPO3\CMS\Extbase\Command\HelpCommandController', array('exception' => $exception));
			}
			$argument->setValue($argumentValue);
		}
	}

	/**
	 * Forwards the request to another command and / or CommandController.
	 *
	 * Request is directly transferred to the other command / controller
	 * without the need for a new request.
	 *
	 * @param string $commandName
	 * @param string $controllerObjectName
	 * @param array $arguments
	 * @return void
	 * @throws StopActionException
	 */
	protected function forward($commandName, $controllerObjectName = NULL, array $arguments = array()) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerCommandName($commandName);
		if ($controllerObjectName !== NULL) {
			$this->request->setControllerObjectName($controllerObjectName);
		}
		$this->request->setArguments($arguments);

		$this->arguments->removeAll();
		throw new StopActionException();
	}

	/**
	 * Calls the specified command method and passes the arguments.
	 *
	 * If the command returns a string, it is appended to the content in the
	 * response object. If the command doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @return void
	 */
	protected function callCommandMethod() {
		$preparedArguments = array();
		/** @var Argument $argument */
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		$commandResult = call_user_func_array(array($this, $this->commandMethodName), $preparedArguments);

		if (is_string($commandResult) && strlen($commandResult) > 0) {
			$this->response->appendContent($commandResult);
		} elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
			$this->response->appendContent((string) $commandResult);
		}
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
	 * Formats the given text to fit into MAXIMUM_LINE_LENGTH and outputs it to the
	 * console window
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @param integer $leftPadding The number of spaces to use for indentation
	 * @return void
	 * @see outputLine()
	 */
	protected function outputFormatted($text = '', array $arguments = array(), $leftPadding = 0) {
		$lines = explode(PHP_EOL, $text);
		foreach ($lines as $line) {
			$formattedText = str_repeat(' ', $leftPadding) . wordwrap($line, self::MAXIMUM_LINE_LENGTH - $leftPadding, PHP_EOL . str_repeat(' ', $leftPadding), TRUE);
			$this->outputLine($formattedText, $arguments);
		}
	}

	/**
	 * Renders a table like output of the given $rows
	 *
	 * @param array $rows
	 * @param array $headers
	 */
	protected function outputTable($rows, $headers = NULL) {
		$tableHelper = $this->getTableHelper();
		if ($headers !== NULL) {
			$tableHelper->setHeaders($headers);
		}
		$tableHelper->setRows($rows);
		$tableHelper->render($this->output);
	}

	/**
	 * Asks the user to select a value
	 *
	 * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
	 * @param array $choices List of choices to pick from
	 * @param boolean $default The default answer if the user enters nothing
	 * @param boolean $multiSelect If TRUE the result will be an array with the selected options. Multiple options can be given separated by commas
	 * @param boolean|integer $attempts Max number of times to ask before giving up (false by default, which means infinite)
	 * @return integer|string|array The selected value or values (the key of the choices array)
	 * @throws \InvalidArgumentException
	 */
	protected function select($question, $choices, $default = NULL, $multiSelect = FALSE, $attempts = FALSE) {
		return $this->getDialogHelper()->select($this->output, $question, $choices, $default, $attempts, 'Value "%s" is invalid', $multiSelect);
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
	 * Asks a confirmation to the user.
	 *
	 * The question will be asked until the user answers by nothing, yes, or no.
	 *
	 * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
	 * @param boolean $default The default answer if the user enters nothing
	 * @return boolean true if the user has confirmed, false otherwise
	 */
	protected function askConfirmation($question, $default = TRUE) {
		return $this->getDialogHelper()->askConfirmation($this->output, $question, $default);
	}

	/**
	 * Asks a question to the user, the response is hidden
	 *
	 * @param string|array $question The question. If an array each array item is turned into one line of a multi-line question
	 * @param Boolean $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
	 * @return string The answer
	 * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
	 */
	protected function askHiddenResponse($question, $fallback = TRUE) {
		return $this->getDialogHelper()->askHiddenResponse($this->output, $question, $fallback);
	}

	/**
	 * Asks for a value and validates the response
	 *
	 * The validator receives the data to validate. It must return the
	 * validated data when the data is valid and throw an exception
	 * otherwise.
	 *
	 * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
	 * @param callable $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
	 * @param integer|boolean $attempts Max number of times to ask before giving up (false by default, which means infinite)
	 * @param string $default The default answer if none is given by the user
	 * @param array $autocomplete List of values to autocomplete. This only works if "stty" is installed
	 * @return mixed
	 * @throws \Exception When any of the validators return an error
	 */
	protected function askAndValidate($question, $validator, $attempts = FALSE, $default = NULL, array $autocomplete = NULL) {
		return $this->getDialogHelper()->askAndValidate($this->output, $question, $validator, $attempts, $default, $autocomplete);
	}

	/**
	 * Asks for a value, hide and validates the response
	 *
	 * The validator receives the data to validate. It must return the
	 * validated data when the data is valid and throw an exception
	 * otherwise.
	 *
	 * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
	 * @param callable $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
	 * @param integer|boolean $attempts Max number of times to ask before giving up (false by default, which means infinite)
	 * @param boolean $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
	 * @return string The response
	 * @throws \Exception When any of the validators return an error
	 * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
	 */
	protected function askHiddenResponseAndValidate($question, $validator, $attempts = FALSE, $fallback = TRUE) {
		return $this->getDialogHelper()->askHiddenResponseAndValidate($this->output, $question, $validator, $attempts, $fallback);
	}

	/**
	 * Starts the progress output
	 *
	 * @param integer $max Maximum steps. If NULL an indeterminate progress bar is rendered
	 * @return void
	 */
	protected function progressStart($max = NULL) {
		$this->getProgressHelper()->start($this->output, $max);
	}

	/**
	 * Advances the progress output X steps
	 *
	 * @param integer $step Number of steps to advance
	 * @param Boolean $redraw Whether to redraw or not
	 * @return void
	 * @throws \LogicException
	 */
	protected function progressAdvance($step = 1, $redraw = false) {
		$this->getProgressHelper()->advance($step, $redraw);
	}

	/**
	 * Sets the current progress
	 *
	 * @param integer $current The current progress
	 * @param Boolean $redraw Whether to redraw or not
	 * @return void
	 * @throws \LogicException
	 */
	protected function progressSet($current, $redraw = false) {
		$this->getProgressHelper()->setCurrent($current, $redraw);
	}

	/**
	 * Finishes the progress output
	 *
	 * @return void
	 */
	protected function progressFinish() {
		$this->getProgressHelper()->finish();
	}

	/**
	 * Exits the CLI through the dispatcher
	 * An exit status code can be specified @see http://www.php.net/exit
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @return void
	 * @deprecated since Flow 2.3. This has no advantage over using \exit() any longer
	 */
	protected function quit($exitCode = 0) {
		exit($exitCode);
	}

	/**
	 * Sends the response and exits the CLI without any further code execution
	 * Should be used for commands that flush code caches.
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @return void
	 */
	protected function sendAndExit($exitCode = 0) {
		$this->response->send();
		exit($exitCode);
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

	/**
	 * Returns or initializes the symfony/console ProgressHelper
	 *
	 * @return ProgressHelper
	 */
	protected function getProgressHelper() {
		if ($this->progressHelper === NULL) {
			$this->progressHelper = new ProgressHelper();
		}
		return $this->progressHelper;
	}

	/**
	 * Returns or initializes the symfony/console TableHelper
	 *
	 * @return TableHelper
	 */
	protected function getTableHelper() {
		if ($this->tableHelper === NULL) {
			$this->tableHelper = new TableHelper();
		}
		return $this->tableHelper;
	}

	/**
	 * Creates a logger which outputs to the console
	 *
	 * @param int $minimumLevel Minimum log level that should trigger output
	 * @param array $options Additional options for the console writer
	 * @return LoggerInterface
	 */
	protected function createDefaultLogger($minimumLevel = LogLevel::DEBUG, $options = array()) {
		$options['output'] = $this->output;
		$logger = new Logger(get_class($this));
		$logger->addWriter($minimumLevel, new ConsoleWriter($options));

		return $logger;
	}

}
