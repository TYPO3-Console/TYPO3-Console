<?php
declare(strict_types=1);
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

use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Represents command configuration provided by packages
 */
class CommandConfiguration
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    private $replaces = [];

    private $commandDefinitions = [];

    public function __construct(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
        $this->initialize();
    }

    /**
     * @param mixed $commandConfiguration
     * @param string $packageName
     * @throws RuntimeException
     */
    public static function ensureValidCommandRegistration($commandConfiguration, $packageName)
    {
        if (
            !is_array($commandConfiguration)
            || (isset($commandConfiguration['controllers']) && !is_array($commandConfiguration['controllers']))
            || (isset($commandConfiguration['runLevels']) && !is_array($commandConfiguration['runLevels']))
            || (isset($commandConfiguration['bootingSteps']) && !is_array($commandConfiguration['bootingSteps']))
            || (isset($commandConfiguration['commands']) && !is_array($commandConfiguration['commands']))
            || (isset($commandConfiguration['replace']) && !is_array($commandConfiguration['replace']))
        ) {
            throw new RuntimeException($packageName . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }

    public static function unifyCommandConfiguration(array $commandConfiguration, string $packageName): array
    {
        $commandDefinitions = array_replace($commandConfiguration['commands'] ?? [], self::extractCommandDefinitionsFromControllers($commandConfiguration['controllers'] ?? []));

        foreach ($commandDefinitions as $commandName => $commandConfig) {
            $commandDefinitions[$commandName]['vendor'] = $vendor = $commandConfig['vendor'] ?? $packageName;
            $nameSpacedCommandName = $vendor . ':' . $commandName;
            $nameSpacedCommandCollection = $nameSpacedCommandName;
            if (strrpos($commandName, ':') !== false) {
                $nameSpacedCommandCollection = $vendor . ':' . substr($commandName, 0, strrpos($commandName, ':')) . ':*';
            }
            if (isset($commandConfiguration['runLevels'][$nameSpacedCommandCollection])) {
                $commandDefinitions[$commandName]['runLevel'] = $commandConfiguration['runLevels'][$nameSpacedCommandCollection];
            }
            if (isset($commandConfiguration['runLevels'][$commandName])) {
                $commandDefinitions[$commandName]['runLevel'] = $commandConfiguration['runLevels'][$commandName];
            }
            if (isset($commandConfiguration['runLevels'][$nameSpacedCommandName])) {
                $commandDefinitions[$commandName]['runLevel'] = $commandConfiguration['runLevels'][$nameSpacedCommandName];
            }
            if (isset($commandConfiguration['bootingSteps'][$commandName])) {
                $commandDefinitions[$commandName]['bootingSteps'] = $commandConfiguration['bootingSteps'][$commandName];
            }
            if (isset($commandConfiguration['bootingSteps'][$nameSpacedCommandName])) {
                $commandDefinitions[$commandName]['bootingSteps'] = $commandConfiguration['bootingSteps'][$nameSpacedCommandName];
            }
        }
        if (isset($commandConfiguration['replace'])) {
            $anyCommandName = key($commandDefinitions);
            $commandDefinitions[$anyCommandName]['replace'] = array_merge($commandDefinitions[$anyCommandName]['replace'] ?? [], $commandConfiguration['replace']);
        }

        return $commandDefinitions;
    }

    private static function extractCommandDefinitionsFromControllers(array $controllers): array
    {
        $commandDefinitions = [];
        foreach ($controllers as $controllerClassName) {
            if (!class_exists($controllerClassName)) {
                throw new RuntimeException(sprintf('Command controller class "%s" does not exist.', $controllerClassName), 1520200175);
            }
            foreach (get_class_methods($controllerClassName) as $methodName) {
                if (substr($methodName, -7, 7) === 'Command') {
                    $controllerCommandName = substr($methodName, 0, -7);
                    $classNameParts = explode('\\', $controllerClassName);
                    if (isset($classNameParts[0], $classNameParts[1]) && $classNameParts[0] === 'TYPO3' && $classNameParts[1] === 'CMS') {
                        $classNameParts[0] .= '\\' . $classNameParts[1];
                        unset($classNameParts[1]);
                        $classNameParts = array_values($classNameParts);
                    }
                    $numberOfClassNameParts = count($classNameParts);
                    $vendor = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($classNameParts[1]);
                    $controllerCommandNameSpace = strtolower(substr($classNameParts[$numberOfClassNameParts - 1], 0, -17));
                    $commandName = $controllerCommandNameSpace . ':' . strtolower($controllerCommandName);
                    $commandDefinitions[$commandName]['vendor'] = $vendor;
                    $commandDefinitions[$commandName]['controller'] = $controllerClassName;
                    $commandDefinitions[$commandName]['controllerCommandName'] = $controllerCommandName;
                }
            }
        }

        return $commandDefinitions;
    }

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        return $this->replaces;
    }

    /**
     * @return array
     */
    public function getCommandDefinitions(): array
    {
        return $this->commandDefinitions;
    }

    public function addCommandControllerCommands(array $commandControllers): array
    {
        $addedCommandDefinitions = $this->getCommandDefinitionsForCommands(self::unifyCommandConfiguration(['controllers' => $commandControllers], ''));
        $this->commandDefinitions = array_replace($this->commandDefinitions, $addedCommandDefinitions);

        return $addedCommandDefinitions;
    }

    private function initialize()
    {
        foreach ($this->gatherRawConfig() as $commandConfiguration) {
            $this->commandDefinitions = array_replace($this->commandDefinitions, $this->getCommandDefinitionsForCommands($commandConfiguration));
        }
    }

    private function getCommandDefinitionsForCommands(array $commands): array
    {
        $commandDefinitions = [];
        foreach ($commands as $name => $singleCommandConfiguration) {
            $vendor = $singleCommandConfiguration['vendor'];
            if (isset($singleCommandConfiguration['replace'])) {
                $this->replaces = array_merge($this->replaces, $singleCommandConfiguration['replace']);
            }
            $singleCommandConfiguration['name'] = $name;
            $nameSpacedCommandName = $vendor . ':' . $name;
            if (isset($this->commandDefinitions[$nameSpacedCommandName])) {
                throw new CommandNameAlreadyInUseException('Command "' . $nameSpacedCommandName . '" registered by "' . $vendor . '" is already in use', 1520181870);
            }
            $commandDefinitions[$nameSpacedCommandName] = $singleCommandConfiguration;
        }

        return $commandDefinitions;
    }

    /**
     * @return array
     */
    private function gatherRawConfig(): array
    {
        if (file_exists($commandConfigurationFile = __DIR__ . '/../../../../Configuration/Console/ComposerPackagesCommands.php')) {
            $configuration = require $commandConfigurationFile;
        } else {
            // We only reach this point in non composer mode
            // We ensure that our commands are present, even if we are not an active extension or even not being an extension at all
            $configuration['typo3_console'] = self::unifyCommandConfiguration(require __DIR__ . '/../../../../Configuration/Console/Commands.php', 'typo3_console');
        }
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packageConfig = $this->getConfigFromExtension($package);
            if (!empty($packageConfig)) {
                self::ensureValidCommandRegistration($packageConfig, $package->getPackageKey());
                $configuration[$package->getPackageKey()] = self::unifyCommandConfiguration($packageConfig, $package->getPackageKey());
            }
        }

        return $configuration;
    }

    private function getConfigFromExtension(PackageInterface $package): array
    {
        $commandConfiguration = [];
        if (file_exists($commandConfigurationFile = $package->getPackagePath() . 'Configuration/Console/Commands.php')) {
            $commandConfiguration = require $commandConfigurationFile;
        }
        if (file_exists($commandConfigurationFile = $package->getPackagePath() . 'Configuration/Commands.php')) {
            $commandConfiguration['commands'] = require $commandConfigurationFile;
        }

        return $commandConfiguration;
    }
}
