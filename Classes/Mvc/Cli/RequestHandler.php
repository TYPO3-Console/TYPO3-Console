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
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\ExtbaseCommand;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\HelpCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
use TYPO3\CMS\Core\Console\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\Response;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * Instance of the Symfony application
     * @var Application
     */
    private $application;

    /**
     * @var string[]
     */
    private $registeredCommandNames = ['help' => 'help'];

    private static $commandsToIgnore = [
        'extbase',
        '_extbase_help',
        '_core_command',
    ];

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->runLevel = $this->bootstrap->getEarlyInstance(RunLevel::class);
        $this->application = new Application('TYPO3 Console', '5.0.0');
    }

    public function handleRequest(InputInterface $input)
    {
        $commandIdentifier = $input->getFirstArgument() ?: '';
        $this->boot();
        $this->populateCommands();
        if ($this->runLevel->isCommandAvailable($commandIdentifier)) {
            $this->bootForCommand($commandIdentifier);
            if ($this->runLevel->getMaximumAvailableRunLevel() === RunLevel::LEVEL_FULL) {
                $this->registerCommandsFromCommandControllers(true);
            }
        }
        $exitCode = $this->application->run();

        $response = new Response();
        $response->setExitCode($exitCode);

        // Store the response for later use in ConsoleApplication
        $this->bootstrap->setEarlyInstance(Response::class, $response);
    }

    /**
     * Executes essential booting steps to be able to accumulate commands
     */
    private function boot()
    {
        $sequence = $this->runLevel->buildSequence(RunLevel::LEVEL_ESSENTIAL);
        $sequence->invoke($this->bootstrap);
    }

    /**
     * @param string $commandIdentifier
     */
    private function bootForCommand($commandIdentifier)
    {
        $sequence = $this->runLevel->buildSequenceForCommand($commandIdentifier);
        $sequence->invoke($this->bootstrap);
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
     * Find all Configuration/Commands.php files of extensions and create a registry from it.
     * The file should return an array with a command key as key and the command description
     * as value. The command description must be an array and have a class key that defines
     * the class name of the command. Example:
     *
     * <?php
     * return [
     *     'backend:lock' => [
     *         'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
     *     ],
     * ];
     *
     * @throws CommandNameAlreadyInUseException
     */
    private function populateCommands()
    {
        $this->application->add(new HelpCommand('help'));
        $this->registerCommandsFromCommandControllers();

        foreach ($this->bootstrap->getEarlyInstance(PackageManager::class)->getActivePackages() as $package) {
            $commandsOfExtension = $package->getPackagePath() . 'Configuration/Commands.php';
            if (@is_file($commandsOfExtension)) {
                $commands = require_once $commandsOfExtension;
                if (is_array($commands)) {
                    foreach ($commands as $commandName => $commandConfig) {
                        if (in_array($commandName, self::$commandsToIgnore, true)) {
                            continue;
                        }
                        if (isset($this->registeredCommandNames[$commandName])) {
                            $namespace = 'typo3';
                            if (strpos($package->getPackagePath(), 'typo3conf/ext/') !== false) {
                                $namespace = $package->getPackageKey();
                            }
                            $commandName = $namespace . ':' . $commandName;
                        }
                        if (isset($this->registeredCommandNames[$commandName])) {
                            throw new CommandNameAlreadyInUseException(
                                'Command "' . $commandName . '" registered by "' . $package->getPackageKey() . '" is already in use',
                                1484486383
                            );
                        }
                        if ($this->runLevel->getMaximumAvailableRunLevel() === RunLevel::LEVEL_COMPILE && !$this->runLevel->isCommandAvailable($commandName)) {
                            continue;
                        }
                        $command = GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                        $this->application->add($command);
                        $this->registeredCommandNames[$commandName] = $commandName;
                    }
                }
            }
        }
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

    /**
     * @param bool $onlyNew
     * @throws CommandNameAlreadyInUseException
     */
    private function registerCommandsFromCommandControllers($onlyNew = false)
    {
        $commandManager = $this->bootstrap->getEarlyInstance(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager::class);
        foreach ($commandManager->getAvailableCommands($onlyNew) as $command) {
            $commandName = $commandManager->getShortestIdentifierForCommand($command);
            $fullCommandName = $command->getCommandIdentifier();
            if ($fullCommandName === 'typo3_console:help:help') {
                continue;
            }
            if (isset($this->registeredCommandNames[$commandName])) {
                $commandName = $fullCommandName;
            }
            if (isset($this->registeredCommandNames[$commandName])) {
                throw new CommandNameAlreadyInUseException('Command "' . $commandName . '" registered by "' . explode(':', $fullCommandName)[0] . '" is already in use', 1484486383);
            }
            if ($this->runLevel->getMaximumAvailableRunLevel() === RunLevel::LEVEL_COMPILE && !$this->runLevel->isCommandAvailable($commandName)) {
                continue;
            }
            $extbaseCommand = GeneralUtility::makeInstance(ExtbaseCommand::class, $commandName);
            $extbaseCommand->setExtbaseCommand($command);
            if ($command->isInternal()) {
                $extbaseCommand->setHidden(true);
            }

            $this->application->add($extbaseCommand);
            $this->registeredCommandNames[$commandName] = $commandName;
        }
    }
}
