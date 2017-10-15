<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony;

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
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\CommandControllerCommand;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\HelpCommand;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\ListCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Represents the complete console application
 */
class Application extends BaseApplication
{
    const TYPO3_CONSOLE_VERSION = '5.0.0';

    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * @var bool
     */
    private $composerManaged;

    public function __construct(RunLevel $runLevel = null, bool $composerManaged = true)
    {
        parent::__construct('TYPO3 Console', self::TYPO3_CONSOLE_VERSION);
        $this->runLevel = $runLevel;
        $this->composerManaged = $composerManaged;
        $this->setAutoExit(false);
    }

    /**
     * Whether the application can run all commands
     * It will return false when TYPO3 is not fully set up yet,
     * e.g. directly after a composer install
     *
     * @return bool
     */
    public function isFullyCapable(): bool
    {
        return $this->runLevel->getMaximumAvailableRunLevel() === RunLevel::LEVEL_FULL;
    }

    /**
     * Whether this application is composer managed.
     * Can be used to enable or disable commands or arguments/ options
     *
     * @return bool
     */
    public function isComposerManaged(): bool
    {
        return $this->composerManaged;
    }

    /**
     * Checks if the given command can be executed in current application state
     *
     * @param Command $command
     * @return bool
     */
    public function isCommandAvailable(Command $command): bool
    {
        return $this->runLevel->isCommandAvailable($command->getName());
    }

    /**
     * Add commands, but check their availability against
     * the current application state before doing so
     *
     * @param iterable $commands
     */
    public function addCommandsIfAvailable(/* iterable */ $commands)
    {
        foreach ($commands as $command) {
            if ($command instanceof CommandControllerCommand || $this->isCommandAvailable($command)) {
                $this->add($command);
            }
        }
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @throws LogicException
     * @return Command[] An array of default Command instances
     */
    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new ListCommand()];
    }

    /**
     * Add default set of styles to the output
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);
        $output->getFormatter()->setStyle('b', new OutputFormatterStyle(null, null, ['bold']));
        $output->getFormatter()->setStyle('i', new OutputFormatterStyle('black', 'white'));
        $output->getFormatter()->setStyle('u', new OutputFormatterStyle(null, null, ['underscore']));
        $output->getFormatter()->setStyle('em', new OutputFormatterStyle(null, null, ['reverse']));
        $output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, ['conceal']));
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
        $output->getFormatter()->setStyle('ins', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('del', new OutputFormatterStyle('red'));
        $output->getFormatter()->setStyle('code', new OutputFormatterStyle(null, null, ['bold']));
    }
}
