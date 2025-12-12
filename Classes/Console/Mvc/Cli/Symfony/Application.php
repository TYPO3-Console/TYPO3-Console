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

use Helhum\Typo3Console\Error\ExceptionRenderer;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Represents the complete console application
 */
class Application extends BaseApplication
{
    const TYPO3_CONSOLE_VERSION = '8.3.1';
    const COMMAND_NAME = 'typo3';

    public function __construct()
    {
        parent::__construct('TYPO3 Console', self::TYPO3_CONSOLE_VERSION);
    }

    public function getLongVersion(): string
    {
        return sprintf(
            'TYPO3 CMS <info>%s</info> (<comment>Application Context:</comment> <info>%s</info>)',
            (new Typo3Version())->getVersion(),
            Environment::getContext()
        )
            . chr(10)
            . chr(10)
            . 'Runtime: '
            . sprintf('PHP <info>%s</info> - ', PHP_VERSION)
            . parent::getLongVersion();
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
        return Bootstrap::checkIfEssentialConfigurationExists(new ConfigurationManager());
    }

    /**
     * Whether errors occurred during booting
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return false;
    }

    /**
     * Checks if the given command can be executed in current application state
     *
     * @param Command $command
     * @return bool
     */
    public function isCommandAvailable(Command $command): bool
    {
        return true;
    }

    public function getErroredCommands(): array
    {
        return [];
    }

    public function renderThrowable(\Throwable $exception, OutputInterface $output): void
    {
        (new ExceptionRenderer())->render($exception, $output, $this);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display help for the given command. When no command is given display help for the <info>list</info> command'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi', '', InputOption::VALUE_NEGATABLE, 'Force (or disable --no-ansi) ANSI output', null),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ]);
    }

    /**
     * Add default set of styles to the output
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function configureIO(InputInterface $input, OutputInterface $output): void
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
