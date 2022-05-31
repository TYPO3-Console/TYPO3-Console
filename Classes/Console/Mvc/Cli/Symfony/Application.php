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
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\ErroredCommand;
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
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Represents the complete console application
 */
class Application extends BaseApplication
{
    const TYPO3_CONSOLE_VERSION = '7.1.2';
    const COMMAND_NAME = 'typo3cms';

    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * @var bool
     */
    private $composerManaged;

    /**
     * @var ErroredCommand[]
     */
    private $erroredCommands = [];

    public function __construct(RunLevel $runLevel = null, bool $composerManaged = true)
    {
        parent::__construct('TYPO3 Console', self::TYPO3_CONSOLE_VERSION);
        $this->runLevel = $runLevel;
        $this->composerManaged = $composerManaged;
        $this->setAutoExit(false);
    }

    public function getLongVersion(): string
    {
        return parent::getLongVersion()
            . chr(10)
            . sprintf(
                'TYPO3 CMS <info>%s</info> (<comment>Application Context:</comment> <info>%s</info>)',
                (new Typo3Version())->getVersion(),
                Environment::getContext()
            );
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
        return $this->runLevel->isCommandAvailable($command->getName()) || $this->runLevel->isInternalCommand($command->getName());
    }

    /**
     * @return ErroredCommand[]
     */
    public function getErroredCommands(): array
    {
        return $this->erroredCommands;
    }

    public function renderThrowable(\Throwable $exception, OutputInterface $output): void
    {
        if ($exception instanceof CommandNotAvailableException) {
            $helper = new SymfonyStyle(new ArgvInput(), $output);
            $helper->getErrorStyle()->block(
                [
                    sprintf(
                        'Command "%s" cannot be run, because it needs a fully set up TYPO3 system.'
                        . PHP_EOL
                        . 'Your system currently lacks an essential configuration file (LocalConfiguration.php).',
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

    public function add(Command $command)
    {
        if ($command instanceof ErroredCommand) {
            $this->erroredCommands[$command->getName()] = $command;
        }

        return parent::add($command);
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

        if ($bootingError = $this->runLevel->getError($command->getName())) {
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
        if (!$this->runLevel->getError() && !$this->isCommandAvailable($command)) {
            throw new CommandNotAvailableException($command->getName());
        }
    }

    private function ensureStableEnvironmentForCommand(Command $command, bool $environmentIsVerbose)
    {
        $bootingError = $this->runLevel->getError($command->getName());
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

        // Reverting https://github.com/symfony/symfony/pull/33897 until this is resolved: https://github.com/symfony/symfony/issues/36565
        if (function_exists('posix_isatty') && getenv('SHELL_INTERACTIVE') === false && $input->isInteractive()) {
            $inputStream = null;
            if ($input instanceof StreamableInputInterface) {
                $inputStream = $input->getStream();
            }
            if (!@posix_isatty($inputStream)) {
                $input->setInteractive(false);
            }
        }
    }
}
