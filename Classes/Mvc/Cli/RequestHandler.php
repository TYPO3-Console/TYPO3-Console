<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * The generic command line interface request handler for the MVC framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface
{
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
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function handleRequest()
    {
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
        $this->request = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder::class)->build($commandLine, $callingScript);
        $this->response = new \TYPO3\CMS\Extbase\Mvc\Cli\Response();
        $this->dispatcher->dispatch($this->request, $this->response);

        $this->response->send();
        $this->shutdown();
    }

    /**
     * @param string $commandIdentifier
     */
    protected function boot($commandIdentifier)
    {
        $sequence = $this->bootstrap->buildBootingSequenceForCommand($commandIdentifier);
        $sequence->invoke($this->bootstrap);

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->dispatcher = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Dispatcher::class);
    }

    protected function shutdown()
    {
        $this->bootstrap->shutdown();
        exit($this->response->getExitCode());
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 100;
    }

    /**
     * Checks if the request handler can handle the current request.
     *
     * @return bool true if it can handle the request, otherwise false
     * @api
     */
    public function canHandleRequest()
    {
        return PHP_SAPI === 'cli' && isset($_SERVER['argc']) && isset($_SERVER['argv']);
    }
}
