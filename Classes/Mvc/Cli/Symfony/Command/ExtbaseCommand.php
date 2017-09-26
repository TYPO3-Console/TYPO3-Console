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

use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Wrapper to turn an Extbase command from a command controller into a Symfony Command
 */
class ExtbaseCommand extends Command
{
    /**
     * Extbase command
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command
     */
    private $command;

    /**
     * @var Application
     */
    private $application;

    public function isEnabled()
    {
        if (!$this->application->isFullyCapable()
            && in_array($this->getName(), [
                // Although these commands are technically available
                // they call other hidden commands in sub processes
                // that need all capabilities. Therefore we disable these commands here.
                // This can be removed, once the implement Symfony commands directly.
                'upgrade:all',
                'upgrade:list',
                'upgrade:wizard',
            ], true)
        ) {
            return false;
        }
        return $this->application->isCommandAvailable($this);
    }

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
     * @param BaseApplication $application An Application instance
     */
    public function setApplication(BaseApplication $application = null)
    {
        if ($application !== null && !$application instanceof Application) {
            throw new \RuntimeException('Extbase commands only work with TYPO3 Console Applications', 1506381781);
        }
        $this->application = $application;
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
        $response = (new RequestHandler())->handle($_SERVER['argv'], $input, $output);
        return $response->getExitCode();
    }
}
