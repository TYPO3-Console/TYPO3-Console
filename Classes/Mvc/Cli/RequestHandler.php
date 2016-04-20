<?php
namespace Helhum\Typo3Console\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Core\Booting\Scripts;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\Flow\Core\Bootstrap;

/**
 * The generic command line interface request handler for the MVC framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface {

	/**
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Response
	 */
	protected $response;

	/**
	 * @var ConsoleBootstrap
	 */
	protected $bootstrap;

	/**
	 * Constructor
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles the request
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface
	 */
	public function handleRequest() {
		// help command by default
		if ($_SERVER['argc'] === 1) {
			$_SERVER['argc'] = 2;
			$_SERVER['argv'][] = 'help';
		}

		$commandLine = $_SERVER['argv'];
		$callingScript = array_shift($commandLine);
		if ($callingScript !== $_SERVER['_']) {
			$callingScript = $_SERVER['_'] . ' ' . $callingScript;
		}

		$this->boot($_SERVER['argv'][1]);
		$this->request = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\RequestBuilder')->build($commandLine, $callingScript);
		$this->response = new \TYPO3\CMS\Extbase\Mvc\Cli\Response();
		$this->dispatcher->dispatch($this->request, $this->response);

		$this->response->send();
		$this->shutdown();
	}

	/**
	 * @param string $commandIdentifier
	 */
	protected function boot($commandIdentifier) {
		$sequence = $this->bootstrap->buildBootingSequenceForCommand($commandIdentifier);
		$sequence->invoke($this->bootstrap);

		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->dispatcher = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher');
		Scripts::overrideImplementation('TYPO3\CMS\Extbase\Command\HelpCommandController', 'Helhum\Typo3Console\Command\HelpCommandController');
	}

	/**
	 *
	 */
	protected function shutdown() {
		$this->bootstrap->shutdown();
		exit($this->response->getExitCode());
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 */
	public function getPriority() {
		return 100;
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 * @api
	 */
	public function canHandleRequest() {
		return PHP_SAPI === 'cli' && isset($_SERVER['argc']) && isset($_SERVER['argv']);
	}
}
