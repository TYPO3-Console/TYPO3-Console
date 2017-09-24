<?php
namespace Helhum\Typo3Console\Core;

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

use Composer\Autoload\ClassLoader;
use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Error\ExceptionHandler;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use Symfony\Component\Console\Input\ArgvInput;
use TYPO3\CMS\Core\Core\ApplicationInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;
use TYPO3\CMS\Extbase\Mvc\Cli\Response;

/**
 * @internal
 */
class ConsoleApplication implements ApplicationInterface
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var int
     */
    private $typo3Branch;

    public function __construct(\Composer\Autoload\ClassLoader $classLoader)
    {
        $this->ensureRequiredEnvironment();
        $this->bootstrap = Bootstrap::getInstance();
        $this->bootstrap->initializeClassLoader($classLoader);
        $this->detectTypo3Branch();
    }

    /**
     * Bootstraps the minimal infrastructure, registers a request handler and
     * then passes control over to that request handler.
     *
     * @param callable|null $execute
     */
    public function run(callable $execute = null)
    {
        $this->boot();

        if ($execute !== null) {
            call_user_func($execute);
        }

        $this->bootstrap->registerRequestHandlerImplementation(RequestHandler::class);
        $this->bootstrap->handleRequest(new ArgvInput());

        $this->shutdown();
    }

    /**
     * Checks PHP sapi type and sets required PHP options
     */
    private function ensureRequiredEnvironment()
    {
        if (PHP_SAPI !== 'cli' || !isset($_SERVER['argc'], $_SERVER['argv'])) {
            echo 'The command line must be executed with a cli PHP binary! The current PHP sapi type is "' . PHP_SAPI . '".' . PHP_EOL;
            exit(1);
        }
        if (ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '-1');
        }
        if (ini_get('max_execution_time') !== '0') {
            @ini_set('max_execution_time', '0');
        }
    }

    private function detectTypo3Branch()
    {
        $this->typo3Branch = 8;
        if (!method_exists($this->bootstrap, 'setCacheHashOptions')) {
            $this->typo3Branch = 9;
        } elseif (!method_exists($this->bootstrap, 'setRequestType')) {
            $this->typo3Branch = 7;
        }
    }

    /**
     * Bootstraps the minimal infrastructure, but does not execute any command
     */
    private function boot()
    {
        $this->defineBaseConstants();
        $this->initializeCompatibilityLayer();
        $this->bootstrap->baseSetup();
        // I want to see deprecation messages
        error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));

        $this->requireLibraries();
        $this->initializePackageManagement();

        if (!class_exists(RunLevel::class)) {
            echo sprintf('Could not initialize TYPO3 Console for TYPO3 in path %s.', PATH_site) . PHP_EOL;
            echo 'This most likely happened because you have a console code checkout in typo3conf/ext/typo3_console,' . PHP_EOL;
            echo 'but TYPO3 Console is not set up as extension. If you want to use it as extension,' . PHP_EOL;
            echo 'please download it from https://typo3.org/extensions/repository/view/typo3_console' . PHP_EOL;
            echo 'or install it properly using Composer.' . PHP_EOL;
            exit(1);
        }

        $this->bootstrap->setEarlyInstance(RunLevel::class, new RunLevel());
        $exceptionHandler = new ExceptionHandler();
        set_exception_handler([$exceptionHandler, 'handleException']);
        $this->initializeCommandManager();
        $this->registerCommands();
    }

    private function shutdown()
    {
        /** @var Response $response */
        $response = $this->bootstrap->getEarlyInstance(Response::class);
        $this->bootstrap->shutdown();
        exit($response->getExitCode());
    }

    /**
     * Define constants and variables
     */
    private function defineBaseConstants()
    {
        define('TYPO3_MODE', 'BE');
        define('PATH_site', \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')) . '/');
        define('PATH_thisScript', PATH_site . 'typo3/index.php');

        if ($this->typo3Branch > 7) {
            $this->bootstrap->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
        } else {
            // @deprecated can be removed once TYPO3 7 support is removed
            define('TYPO3_cliMode', true);
            define('TYPO3_REQUESTTYPE_FE', 1);
            define('TYPO3_REQUESTTYPE_BE', 2);
            define('TYPO3_REQUESTTYPE_CLI', 4);
            define('TYPO3_REQUESTTYPE_AJAX', 8);
            define('TYPO3_REQUESTTYPE_INSTALL', 16);
            define('TYPO3_REQUESTTYPE', TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
        }
    }

    /**
     * If detected TYPO3 version does not match the main supported version,
     * overlay compatibility classes for the detected branch, by registering
     * an autoloader and aliasing the compatibility class with the original class name.
     */
    private function initializeCompatibilityLayer()
    {
        if ($this->typo3Branch === 8) {
            return;
        }
        $compatibilityClassesPath = __DIR__ . '/../../Compatibility/LTS' . $this->typo3Branch;
        $compatibilityNamespace = 'Helhum\\Typo3Console\\LTS' . $this->typo3Branch . '\\';
        $classLoader = new ClassLoader();
        $classLoader->addPsr4($compatibilityNamespace, $compatibilityClassesPath);
        spl_autoload_register(function ($className) use ($classLoader, $compatibilityNamespace) {
            $compatibilityClassName = str_replace('Helhum\\Typo3Console\\', $compatibilityNamespace, $className);
            if ($file = $classLoader->findFile($compatibilityClassName)) {
                require $file;
                class_alias($compatibilityClassName, $className);
            }
        }, true, true);
    }

    /**
     * Require libraries, in case TYPO3 is in non Composer mode
     */
    private function requireLibraries()
    {
        if (@file_exists($pharFile = dirname(dirname(__DIR__)) . '/Libraries/symfony-process.phar')) {
            include 'phar://' . $pharFile . '/vendor/autoload.php';
        }
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @return void
     */
    private function initializePackageManagement()
    {
        // Make sure the package manager class is available
        // the extension might not be active yet, but will be activated in this class
        if (!Bootstrap::usesComposerClassLoading()) {
            require __DIR__ . '/../Package/UncachedPackageManager.php';
        }
        $packageManager = new \Helhum\Typo3Console\Package\UncachedPackageManager();
        $this->bootstrap->setEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $dependencyResolver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\DependencyResolver::class);
        $dependencyResolver->injectDependencyOrderingService(
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\DependencyOrderingService::class)
        );
        $packageManager->injectDependencyResolver($dependencyResolver);
        $packageManager->init();
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
    }

    private function initializeCommandManager()
    {
        $commandManager = GeneralUtility::makeInstance(\Helhum\Typo3Console\Mvc\Cli\CommandManager::class);
        $this->bootstrap->setEarlyInstance(CommandManager::class, $commandManager);
        GeneralUtility::setSingletonInstance(CommandManager::class, $commandManager);
    }

    private function registerCommands()
    {
        foreach ($this->getCommandConfigurations() as $packageKey => $commandConfiguration) {
            $this->registerCommandsFromConfiguration($commandConfiguration, $packageKey);
        }
    }

    /**
     * @return array
     */
    private function getCommandConfigurations()
    {
        if (file_exists($commandConfigurationFile = __DIR__ . '/../../Configuration/Console/AllCommands.php')) {
            return require $commandConfigurationFile;
        }
        $commandConfigurationFiles = [];
        /** @var PackageManager $packageManager */
        $packageManager = $this->bootstrap->getEarlyInstance(PackageManager::class);
        foreach ($packageManager->getActivePackages() as $package) {
            $possibleCommandsFileName = $package->getPackagePath() . '/Configuration/Console/Commands.php';
            if (!file_exists($possibleCommandsFileName)) {
                continue;
            }
            $commandConfigurationFiles[$package->getPackageKey()] = require $possibleCommandsFileName;
        }
        return $commandConfigurationFiles;
    }

    /**
     * @param $commandConfiguration
     * @param $packageKey
     */
    private function registerCommandsFromConfiguration($commandConfiguration, $packageKey)
    {
        $this->ensureValidCommandsConfiguration($commandConfiguration, $packageKey);

        foreach ($commandConfiguration['controllers'] as $controller) {
            $this->bootstrap->getEarlyInstance(CommandManager::class)->registerCommandController($controller);
        }
        foreach ($commandConfiguration['runLevels'] as $commandIdentifier => $runLevel) {
            $this->bootstrap->getEarlyInstance(RunLevel::class)->setRunLevelForCommand($commandIdentifier, $runLevel);
        }
        foreach ($commandConfiguration['bootingSteps'] as $commandIdentifier => $bootingSteps) {
            foreach ((array)$bootingSteps as $bootingStep) {
                $this->bootstrap->getEarlyInstance(RunLevel::class)->addBootingStepForCommand($commandIdentifier, $bootingStep);
            }
        }
    }

    /**
     * @param mixed $commandConfiguration
     * @param string $packageKey
     * @throws \RuntimeException
     */
    private function ensureValidCommandsConfiguration($commandConfiguration, $packageKey)
    {
        if (
            !is_array($commandConfiguration)
            || count($commandConfiguration) !== 3
            || !isset($commandConfiguration['controllers'], $commandConfiguration['runLevels'], $commandConfiguration['bootingSteps'])
            || !is_array($commandConfiguration['controllers'])
            || !is_array($commandConfiguration['runLevels'])
            || !is_array($commandConfiguration['bootingSteps'])
        ) {
            throw new \RuntimeException($packageKey . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }
}
