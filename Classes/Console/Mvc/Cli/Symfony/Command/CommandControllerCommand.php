<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Mvc\Cli\Command as CommandDefinition;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Wrapper to turn a command controller commands into a Symfony Command
 */
class CommandControllerCommand extends Command
{
    /**
     * Only use for rendering the reference
     *
     * @var bool
     * @internal
     */
    public static $rendersReference = false;

    /**
     * @var CommandDefinition
     */
    private $commandDefinition;

    /**
     * @var Application
     */
    private $application;

    public function __construct($name, CommandDefinition $commandDefinition)
    {
        $this->commandDefinition = $commandDefinition;
        parent::__construct($name);
    }

    public function getNativeDefinition()
    {
        $commandInputDefinition = new InputDefinition($this->commandDefinition->getInputDefinitions(true));
        $commandInputDefinition->addOptions($this->application->getDefinition()->getOptions());

        return $commandInputDefinition;
    }

    public function getRelatedCommandNames()
    {
        return $this->commandDefinition->getRelatedCommandIdentifiers();
    }

    public function isEnabled(): bool
    {
        if (self::$rendersReference) {
            return true;
        }
        if ($this->application->isComposerManaged()
            && $this->getName() === 'extension:dumpautoload'
        ) {
            return false;
        }
        if (!$this->application->isFullyCapable()
            && in_array($this->getName(), [
                // Although these commands are technically available
                // they call other hidden commands in sub processes
                // that need all capabilities. Therefore we disable these commands here.
                // This can be removed, once they implement Symfony commands directly.
                'upgrade:all',
                'upgrade:list',
                'upgrade:wizard',
            ], true)
        ) {
            return false;
        }
        if ($this->getName() === 'cache:flushcomplete') {
            return true;
        }

        return $this->application->isCommandAvailable($this);
    }

    /**
     * Extbase has its own validation logic, so it is disabled in this place
     */
    protected function configure()
    {
        $this->setDescription($this->commandDefinition->getShortDescription());
        $this->setHelp($this->commandDefinition->getDescription());
        $strict = $this->commandDefinition->shouldValidateInputStrict();
        if (!$strict) {
            $this->ignoreValidationErrors();
        }
        $this->setDefinition($this->commandDefinition->getInputDefinitions());
    }

    /**
     * Sets the application instance for this command.
     * It is used later for isEnabled()
     *
     * @param BaseApplication $application An Application instance
     * @throws RuntimeException
     */
    public function setApplication(BaseApplication $application = null)
    {
        if ($application !== null && !$application instanceof Application) {
            throw new RuntimeException('Command controller commands only work with TYPO3 Console Applications', 1506381781);
        }
        $this->application = $application;
        parent::setApplication($application);
    }

    public function getSynopsis($short = false)
    {
        return sprintf('%s %s', $this->getName(), $this->commandDefinition->getSynopsis($short));
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
        // @deprecated in 5.0 will be removed in 6.0
        $givenCommandName = $input->getArgument('command');
        if ($givenCommandName === $this->getAliases()[0]) {
            $output->writeln('<warning>Specifying the full command name is deprecated.</warning>');
            $output->writeln(sprintf('<warning>Please use "%s" as command name instead.</warning>', $this->getName()));
        }
        $response = (new RequestHandler())->handle($this->commandDefinition, $input, $output);

        return $response->getExitCode();
    }

    public function isHidden(): bool
    {
        if ($this->application->isComposerManaged()
            && in_array($this->getName(), [
                // Hide commands than don't make sense when application is composer managed, but should still work for a while
                'extension:activate',
                'extension:deactivate',
                'extension:removeinactive',
            ], true)
        ) {
            if (self::$rendersReference) {
                return false;
            }

            return true;
        }

        return $this->commandDefinition->isInternal();
    }
}
