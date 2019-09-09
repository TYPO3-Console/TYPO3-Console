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
use TYPO3\CMS\Core\Core\Environment;
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
        $commandDefinitions = [];

        foreach ($commandConfiguration['commands'] ?? [] as $commandName => $commandConfig) {
            $vendor = $commandConfig['vendor'] ?? $packageName;
            $nameSpacedCommandName = $vendor . ':' . $commandName;
            $commandConfig['vendor'] = $vendor;
            $commandConfig['name'] = $commandName;
            $commandConfig['nameSpacedName'] = $nameSpacedCommandName;
            $nameSpacedCommandCollection = $nameSpacedCommandName;
            if (strrpos($commandName, ':') !== false) {
                $nameSpacedCommandCollection = $vendor . ':' . substr($commandName, 0, strrpos($commandName, ':')) . ':*';
            }
            if (isset($commandConfiguration['runLevels'][$nameSpacedCommandCollection])) {
                $commandConfig['runLevel'] = $commandConfiguration['runLevels'][$nameSpacedCommandCollection];
            }
            if (isset($commandConfiguration['runLevels'][$commandName])) {
                $commandConfig['runLevel'] = $commandConfiguration['runLevels'][$commandName];
            }
            if (isset($commandConfiguration['runLevels'][$nameSpacedCommandName])) {
                $commandConfig['runLevel'] = $commandConfiguration['runLevels'][$nameSpacedCommandName];
            }
            if (isset($commandConfiguration['bootingSteps'][$commandName])) {
                $commandConfig['bootingSteps'] = $commandConfiguration['bootingSteps'][$commandName];
            }
            if (isset($commandConfiguration['bootingSteps'][$nameSpacedCommandName])) {
                $commandConfig['bootingSteps'] = $commandConfiguration['bootingSteps'][$nameSpacedCommandName];
            }
            $commandDefinitions[] = $commandConfig;
        }
        if (isset($commandConfiguration['replace'])) {
            $commandDefinitions[0]['replace'] = array_merge($commandDefinitions[0]['replace'] ?? [], $commandConfiguration['replace']);
        }

        return array_replace($commandDefinitions, self::extractCommandDefinitionsFromControllers($commandConfiguration['controllers'] ?? [], $packageName === '_lateCommands'));
    }

    private static function extractCommandDefinitionsFromControllers(array $controllers, bool $lateCommand): array
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
                    $namespacedCommandName = $vendor . ':' . $commandName;
                    $commandDefinitions[] = [
                        'vendor' => $vendor,
                        'name' => $commandName,
                        'nameSpacedName' => $namespacedCommandName,
                        'controller' => $controllerClassName,
                        'controllerCommandName' => $controllerCommandName,
                        'lateCommand' => $lateCommand,
                    ];
                }
            }
        }

        return $commandDefinitions;
    }

    /**
     * @return array
     */
    public function getCommandDefinitions(): array
    {
        return $this->commandDefinitions;
    }

    /**
     * @deprecated will be removed with 6.0
     *
     * @param array $commandControllers
     * @return array
     */
    public function addCommandControllerCommands(array $commandControllers): array
    {
        $addedCommandDefinitions = self::unifyCommandConfiguration(['controllers' => $commandControllers], '_lateCommands');
        $this->commandDefinitions = array_merge($this->commandDefinitions, $addedCommandDefinitions);

        if (!empty($addedCommandDefinitions[1]) && $addedCommandDefinitions[0]['name'] !== 'help:error') {
            trigger_error('Registering commands via $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'extbase\'][\'commandControllers\'] is deprecated and will be removed with 6.0. Register Symfony commands in Configuration/Commands.php instead.', E_USER_DEPRECATED);
        }

        return $addedCommandDefinitions;
    }

    private function initialize()
    {
        $this->commandDefinitions = array_merge([], ...$this->gatherRawConfig());
    }

    /**
     * @return array
     */
    private function gatherRawConfig(): array
    {
        $configuration = require __DIR__ . '/../../../../Configuration/ComposerPackagesCommands.php';
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packageConfig = $this->getConfigFromExtension($package);
            if (!empty($packageConfig)) {
                self::ensureValidCommandRegistration($packageConfig, $package->getPackageKey());
                $configuration[] = self::unifyCommandConfiguration($packageConfig, $package->getPackageKey());
            }
        }

        return $configuration;
    }

    private function getConfigFromExtension(PackageInterface $package): array
    {
        $commandConfiguration = [];
        // @deprecated will be removed with 6.0
        if (file_exists($commandConfigurationFile = $package->getPackagePath() . 'Configuration/Console/Commands.php')) {
            if (class_exists(Environment::class)) {
                trigger_error($package->getPackageKey() . ': Configuration/Console/Commands.php for registering commands is deprecated and will be removed with 6.0. Register Symfony commands in Configuration/Commands.php instead.', E_USER_DEPRECATED);
            }
            $commandConfiguration = require $commandConfigurationFile;
        }
        if (file_exists($commandConfigurationFile = $package->getPackagePath() . 'Configuration/Commands.php')) {
            $commandConfiguration['commands'] = require $commandConfigurationFile;
        }

        return $commandConfiguration;
    }
}
