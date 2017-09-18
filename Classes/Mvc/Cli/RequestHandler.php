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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Console\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder;
use TYPO3\CMS\Extbase\Mvc\Cli\Response;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var ConsoleBootstrap
     */
    private $bootstrap;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * Constructor
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function handleRequest(InputInterface $input)
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
        $request = $this->objectManager->get(RequestBuilder::class)->build($commandLine, $callingScript);
        $response = new Response();
        $this->dispatcher->dispatch($request, $response);

        // Store the response for later use in ConsoleApplication
        $this->bootstrap->setEarlyInstance(Response::class, $response);
    }

    /**
     * @param string $commandIdentifier
     */
    protected function boot($commandIdentifier)
    {
        /** @var RunLevel $runLevel */
        $runLevel = $this->bootstrap->getEarlyInstance(RunLevel::class);
        $sequence = $runLevel->buildSequenceForCommand($commandIdentifier);
        $sequence->invoke($this->bootstrap);

        // Late setting of these objects as they depended on booted state
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dispatcher = $this->objectManager->get(Dispatcher::class);
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
     * @param InputInterface $input
     * @return bool true if it can handle the request, otherwise false
     * @api
     */
    public function canHandleRequest(InputInterface $input)
    {
        return true;
    }
}
