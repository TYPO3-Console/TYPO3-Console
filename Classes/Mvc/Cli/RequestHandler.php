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

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class RequestHandler
{
    public function handle(array $commandLine, InputInterface $input = null, OutputInterface $output = null): Response
    {
        $input = $input ?: new ArgvInput();
        $output = $output ?: new SymfonyConsoleOutput();
        $callingScript = array_shift($commandLine);
        if ($callingScript !== $_SERVER['_']) {
            $callingScript = $_SERVER['_'] . ' ' . $callingScript;
        }

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dispatcher = $objectManager->get(Dispatcher::class);

        $request = $objectManager->get(RequestBuilder::class)->build($commandLine, $callingScript);
        $response = new Response();
        $response->setInput($input);
        $response->setOutput($output);
        $dispatcher->dispatch($request, $response);

        return $response;
    }
}
