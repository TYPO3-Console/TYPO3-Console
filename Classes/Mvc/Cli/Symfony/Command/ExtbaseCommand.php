<?php
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Command;

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

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Helhum\Typo3Console\Mvc\Cli\Response;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Wrapper to wrap an Extbase command from a command controller into a Symfony Command
 */
class ExtbaseCommand extends Command
{
    /**
     * Extbase's command
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command
     */
    protected $command;

    /**
     * Extbase has its own validation logic, so it is disabled in this place
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();
    }

    /**
     * Sets the extbase command to be used for fetching the description etc.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command
     */
    public function setExtbaseCommand(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command)
    {
        $this->command = $command;
    }

    /**
     * Sets the application instance for this command.
     * Also uses the setApplication call now, as $this->configure() is called
     * too early
     *
     * @param Application $application An Application instance
     */
    public function setApplication(Application $application = null)
    {
        parent::setApplication($application);
        $this->setDescription($this->command->getShortDescription());
        $this->setHelp($this->command->getDescription());
    }

    /**
     * Executes the command to find any Extbase command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dispatcher = $objectManager->get(Dispatcher::class);

        $request = $objectManager->get(RequestBuilder::class)->build($commandLine, $callingScript);
        $response = new Response();
        $response->setInput($input);
        $response->setOutput($output);
        $dispatcher->dispatch($request, $response);

        return $response->getExitCode();
    }
}
