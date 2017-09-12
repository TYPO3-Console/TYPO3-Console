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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\Booting\Sequence;
use Helhum\Typo3Console\Error\ExceptionHandler;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * Class ConsoleBootstrap
 * @internal
 */
class ConsoleBootstrap extends Bootstrap
{
    /**
     * @var RequestHandlerInterface[]
     */
    private $requestHandlers = [];

    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * @param string $context
     * @return ConsoleBootstrap
     */
    public static function create($context)
    {
        if (self::$instance !== null) {
            throw new \RuntimeException('Cannot create bootstrap once it has been initialized', 1484391221);
        }
        return new self($context);
    }

    /**
     * Bootstraps the minimal infrastructure, but does not execute any command
     *
     * @param \Composer\Autoload\ClassLoader $classLoader
     */
    public function initialize(\Composer\Autoload\ClassLoader $classLoader)
    {
        if (!self::$instance) {
            $this->ensureRequiredEnvironment();
            self::$instance = $this;
            self::$usesComposerClassLoading = class_exists(\Helhum\Typo3Console\Package\UncachedPackageManager::class);
            $this->initializeClassLoader($classLoader);
            // @deprecated in TYPO3 8. Condition will be removed when TYPO3 7.6 support is removed
            if (is_callable([$this, 'setRequestType'])) {
                $this->defineTypo3RequestTypes();
                $this->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
            }
            $this->baseSetup();
            $this->requireLibraries();
            // @deprecated in TYPO3 8 will be removed when TYPO3 7.6 support is removed
            if (!is_callable([$this, 'setRequestType'])) {
                $this->defineTypo3RequestTypes();
            }
            $this->requestId = substr(md5(uniqid('console_request_', true)), 0, 13);
            $this->initializePackageManagement();

            if (!class_exists(RunLevel::class)) {
                echo sprintf('Could not initialize TYPO3 Console for TYPO3 in path %s.', PATH_site) . chr(10);
                echo 'This most likely happened because you have a console code checkout in typo3conf/ext/typo3_console,' . chr(10);
                echo 'but TYPO3 Console is not set up as extension. If you want to use it as extension,' . chr(10);
                echo 'please download it from https://typo3.org/extensions/repository/view/typo3_console' . chr(10);
                echo 'or install it properly using Composer.' . chr(10);
                exit(1);
            }

            $this->runLevel = new RunLevel();
            $this->setEarlyInstance(\Helhum\Typo3Console\Core\Booting\RunLevel::class, $this->runLevel);
            $exceptionHandler = new ExceptionHandler();
            set_exception_handler([$exceptionHandler, 'handleException']);
            $this->initializeCommandManager();
            $this->registerCommands();
        }
    }

    /**
     * Bootstraps the minimal infrastructure, resolves a fitting request handler and
     * then passes control over to that request handler.
     *
     * @param \Composer\Autoload\ClassLoader $classLoader
     * @throws \TYPO3\CMS\Core\Error\Exception
     * @throws \RuntimeException
     */
    public function run(\Composer\Autoload\ClassLoader $classLoader)
    {
        $this->initialize($classLoader);
        $this->registerRequestHandler(new RequestHandler($this));
        $this->resolveCliRequestHandler()->handleRequest();
    }

    /**
     * @param string $runLevel
     * @deprecated Will be removed with 5.0
     */
    public function requestRunLevel($runLevel)
    {
        $sequence = $this->runLevel->buildSequence($runLevel);
        $sequence->invoke($this);
    }

    /**
     * Builds the sequence for the given run level
     *
     * @param $commandIdentifier
     * @return Sequence
     */
    public function buildBootingSequenceForCommand($commandIdentifier)
    {
        return $this->runLevel->buildSequenceForCommand($commandIdentifier);
    }

    /**
     * Sets a run level for a specific command
     *
     * @param $commandIdentifier
     * @param $runLevel
     * @api
     */
    public function setRunLevelForCommand($commandIdentifier, $runLevel)
    {
        $this->runLevel->setRunLevelForCommand($commandIdentifier, $runLevel);
    }

    /**
     * Adds a step to the resolved booting sequence
     *
     * @param string $commandIdentifier
     * @param string $stepIdentifier
     */
    public function addBootingStepForCommand($commandIdentifier, $stepIdentifier)
    {
        $this->runLevel->addBootingStepForCommand($commandIdentifier, $stepIdentifier);
    }

    /**
     * Checks PHP sapi type and sets required PHP options
     */
    private function ensureRequiredEnvironment()
    {
        if (PHP_SAPI !== 'cli') {
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

    /**
     * Registers a request handler which can possibly handle a request.
     *
     * All registered request handlers will be queried if they can handle a request
     * when the bootstrap's run() method is called.
     *
     * @param RequestHandlerInterface $requestHandler
     * @return void
     * @api
     */
    public function registerRequestHandler(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandlers[get_class($requestHandler)] = $requestHandler;
    }

    /**
     * Iterates over the registered request handlers and determines which one fits best.
     *
     * @throws \RuntimeException
     * @return RequestHandlerInterface A request handler
     */
    public function resolveCliRequestHandler()
    {
        $suitableRequestHandlers = [];
        foreach ($this->requestHandlers as $requestHandler) {
            if ($requestHandler->canHandleRequest() > 0) {
                $priority = $requestHandler->getPriority();
                if (isset($suitableRequestHandlers[$priority])) {
                    throw new \RuntimeException('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
                }
                $suitableRequestHandlers[$priority] = $requestHandler;
            }
        }
        if (empty($suitableRequestHandlers)) {
            throw new \RuntimeException('No request handler found that can handle that request.', 1417863426);
        }
        ksort($suitableRequestHandlers);
        return array_pop($suitableRequestHandlers);
    }

    /*
     *  Additional Methods needed for the bootstrap sequences
     */

    private function initializeCommandManager()
    {
        $commandManager = GeneralUtility::makeInstance(\Helhum\Typo3Console\Mvc\Cli\CommandManager::class);
        $this->setEarlyInstance(CommandManager::class, $commandManager);
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
        $packageManager = $this->getEarlyInstance(PackageManager::class);
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
            $this->getEarlyInstance(CommandManager::class)->registerCommandController($controller);
        }
        foreach ($commandConfiguration['runLevels'] as $commandIdentifier => $runLevel) {
            $this->setRunLevelForCommand($commandIdentifier, $runLevel);
        }
        foreach ($commandConfiguration['bootingSteps'] as $commandIdentifier => $bootingSteps) {
            foreach ((array)$bootingSteps as $bootingStep) {
                $this->addBootingStepForCommand($commandIdentifier, $bootingStep);
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

    /**
     * @param string $pathPart
     * @return void
     */
    public function baseSetup($pathPart = '')
    {
        define('TYPO3_MODE', 'BE');
        // @deprecated to define this constant. Can be removed when TYPO3 7 support is removed
        define('TYPO3_cliMode', true);
        parent::baseSetup($pathPart);
        // I want to see deprecation messages
        error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
    }

    /**
     * Require libraries, in case TYPO3 is in non Composer mode
     */
    protected function requireLibraries()
    {
        if (@file_exists($pharFile = dirname(dirname(__DIR__)) . '/Libraries/symfony-process.phar')) {
            include 'phar://' . $pharFile . '/vendor/autoload.php';
        }
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @return void
     */
    public function initializePackageManagement($packageManagerClassName = \Helhum\Typo3Console\Package\UncachedPackageManager::class)
    {
        // Make sure the package manager class is available
        // the extension might not be active yet, but will be activated in this class
        if (!self::usesComposerClassLoading()) {
            require __DIR__ . '/../Package/UncachedPackageManager.php';
        }
        $packageManager = new \Helhum\Typo3Console\Package\UncachedPackageManager();
        $this->setEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $dependencyResolver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\DependencyResolver::class);
        $dependencyResolver->injectDependencyOrderingService(
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\DependencyOrderingService::class)
        );
        $packageManager->injectDependencyResolver($dependencyResolver);
        $packageManager->init($this);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
    }

    public function disableCoreCaches()
    {
        $this->disableCoreCache();
        /** @var PackageManager $packageManager */
        $packageManager = $this->getEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        if ($packageManager->isPackageActive('dbal')) {
            $cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
            $cacheConfigurations['dbal'] = [
                'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                'groups' => [],
            ];
        }
    }

    public function initializeConfigurationManagement()
    {
        $this->populateLocalConfiguration();
        if (!self::usesComposerClassLoading()) {
            $this->initializeRuntimeActivatedPackagesFromConfiguration();
        }
        // Because links might be generated from CLI (e.g. by Solr indexer)
        // We need to properly initialize the cache hash calculator here!
        // @deprecated can be removed if TYPO3 8 support is removed
        if (is_callable([$this, 'setCacheHashOptions'])) {
            $this->setCacheHashOptions();
        }
        $this->setDefaultTimezone();
        // @deprecated can be removed if TYPO3 8 support is removed
        if (is_callable([$this, 'defineUserAgentConstant'])) {
            $this->defineUserAgentConstant();
        }
        // @deprecated can be removed if TYPO3 7 support is removed
        if (is_callable([$this, 'defineDatabaseConstants'])) {
            $this->defineDatabaseConstants();
        }
    }

    /**
     * @deprecated can be removed if TYPO3 7 support is removed (directly use $bootstrap->loadBaseTca())
     */
    public function loadTcaOnly()
    {
        ExtensionManagementUtility::loadBaseTca();
    }

    /**
     * @deprecated can be removed if TYPO3 7 support is removed (directly use $bootstrap->loadExtTables())
     */
    public function loadExtTablesOnly()
    {
        ExtensionManagementUtility::loadExtTables();
        if (is_callable([$this, 'executeExtTablesAdditionalFile'])) {
            $this->executeExtTablesAdditionalFile();
        }
        $this->runExtTablesPostProcessingHooks();
    }

    /**
     * @deprecated can be removed if TYPO3 8 support is removed
     */
    public function initializeDatabaseConnection()
    {
        if (is_callable([$this, 'initializeTypo3DbGlobal'])) {
            $this->initializeTypo3DbGlobal();
        }
    }

    /**
     * Sets up additional configuration applied in all scopes
     *
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function applyAdditionalConfigurationSettings()
    {
        $this->setFinalCachingFrameworkCacheConfiguration();
        // @deprecated can be removed once TYPO3 8.7 support is removed
        if (is_callable([$this, 'defineLoggingAndExceptionConstants'])) {
            $this->defineLoggingAndExceptionConstants();
        }
        $this->unsetReservedGlobalVariables();
        return $this;
    }
}
