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
use Helhum\Typo3Console\Core\Booting\StepFailedException;
use Helhum\Typo3Console\Error\ExceptionRenderer;
use Helhum\Typo3Console\Exception\CommandNotAvailableException;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\HelpCommand;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\ListCommand;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Represents the complete console application
 */
class Application extends BaseApplication
{
    const TYPO3_CONSOLE_VERSION = '6.0.0';
    const COMMAND_NAME = 'typo3cms';

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
     * Whether errors occurred during booting
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->runLevel->getError() !== null;
    }

    /**
     * @param string $runLevel
     * @throws StepFailedException
     */
    public function boot(string $runLevel)
    {
        $this->runLevel->runSequence($runLevel);
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
        if (!$this->isFullyCapable()
            && in_array($command->getName(), [
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
        if ($command->getName() === 'cache:flushcomplete') {
            return true;
        }

        return $this->runLevel->isCommandAvailable($command->getName());
    }

    public function renderException($exception, OutputInterface $output)
    {
        if ($exception instanceof CommandNotAvailableException) {
            $helper = new SymfonyStyle(new ArgvInput(), $output);
            $helper->getErrorStyle()->block(
                [
                    sprintf(
                        'Command "%s" cannot be run, because it needs a fully set up TYPO3 system.'
                        . PHP_EOL
                        . 'Your system currently lacks essential configuration files (LocalConfiguration.php, PackageStates.php).',
                        $exception->getCommandName()
                    ),
                    'Try setting up your system using the "install:setup" command.',
                ],
                null,
                'fg=white;bg=red',
                ' ',
                true
            );

            return;
        }

        (new ExceptionRenderer())->render($exception, $output, $this);
    }

    public function renderThrowable(\Throwable $e, OutputInterface $output): void
    {
        $this->renderException($e, $output);
    }

    /**
     * @param Command $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Throwable
     * @return int
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        $this->ensureCommandAvailable($command);
        $this->ensureStableEnvironmentForCommand($command, $output->isVerbose());

        $exitCode = parent::doRunCommand($command, $input, $output);

        if ($bootingError = $this->runLevel->getError()) {
            $messages = [
                sprintf(
                    'An error occurred while executing "%s".',
                    str_replace('helhum.typo3console:', '', $bootingError->getFailedStep()->getIdentifier())
                ),
                sprintf(
                    'Run "%s --verbose" to see a detailed error message.',
                    $_SERVER['PHP_SELF']
                ),
            ];

            $helper = new SymfonyStyle($input, $output);
            $helper->getErrorStyle()->block(
                $messages,
                null,
                'fg=black;bg=yellow',
                ' ',
                true
            );
        }

        return $exitCode;
    }

    private function ensureCommandAvailable(Command $command)
    {
        $commandName = $command->getName();
        if ($this->runLevel->isCommandAvailable($commandName)) {
            $this->runLevel->runSequenceForCommand($commandName);
        }
        if (!$this->runLevel->getError() && !$this->isCommandAvailable($command)) {
            throw new CommandNotAvailableException($command->getName());
        }
    }

    private function ensureStableEnvironmentForCommand(Command $command, bool $environmentIsVerbose)
    {
        $bootingError = $this->runLevel->getError();
        if ($bootingError && ($environmentIsVerbose || !$this->runLevel->isInternalCommand($command->getName()))) {
            throw $bootingError->getPrevious();
        }
    }

    protected function getDefaultInputDefinition()
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ]);
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
