<?php
namespace Helhum\Typo3Console\Core;

/*
 * This file is part of the TYPO3 console project.
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
use Helhum\Typo3Console\Mvc\Cli\CommandManager;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * Class ConsoleBootstrap
 */
class ConsoleBootstrap extends Bootstrap
{
    /**
     * @var array
     */
    public $commands = [];

    /**
     * @var RequestHandlerInterface[]
     */
    protected $requestHandlers = [];

    /**
     * @var RunLevel
     */
    protected $runLevel;

    /**
     * @var string $context Application context
     */
    public function __construct($context)
    {
        self::$instance = $this;
        $this->ensureRequiredEnvironment();
        parent::__construct($context);
    }

    /**
     * Override parent to calrify return type
     *
     * @return ConsoleBootstrap
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * Bootstraps the minimal infrastructure, resolves a fitting request handler and
     * then passes control over to that request handler.
     * @return ConsoleBootstrap
     */

    /**
     * @param \Composer\Autoload\ClassLoader|NULL $classLoader
     * @return $this
     * @throws \TYPO3\CMS\Core\Error\Exception
     */
    public function run($classLoader = null)
    {
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
        $this->requestId = uniqid('console_request_', true);
        $this->initializePackageManagement();

        $this->runLevel = new RunLevel();
        $this->setEarlyInstance(\Helhum\Typo3Console\Core\Booting\RunLevel::class, $this->runLevel);
        $exceptionHandler = new ExceptionHandler();
        set_exception_handler([$exceptionHandler, 'handleException']);

        $this->initializeCommandManager();
        $this->registerRequestHandler(new RequestHandler($this));

        $requestHandler = $this->resolveCliRequestHandler();
        $requestHandler->handleRequest();
        return $this;
    }

    /**
     * TODO: Add other API that does not depend on bootstrap
     *
     * @param string $runLevel
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
    protected function ensureRequiredEnvironment()
    {
        if (PHP_SAPI !== 'cli') {
            echo 'The command line must be executed with a cli PHP binary! The current PHP sapi type is "' . PHP_SAPI . '".' . PHP_EOL;
            exit(1);
        }
        ini_set('memory_limit', -1);
        set_time_limit(0);
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
     * Returns the command manager which can be used to register commands during package management initialisation
     *
     * @return CommandManager
     * @api
     */
    public function getCommandManager()
    {
        return $this->getEarlyInstance(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager::class);
    }

    /**
     * Iterates over the registered request handlers and determines which one fits best.
     *
     * @return RequestHandlerInterface A request handler
     * @throws \RuntimeException
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

    public function initializeCommandManager()
    {
        $commandManager = Utility\GeneralUtility::makeInstance(\Helhum\Typo3Console\Mvc\Cli\CommandManager::class);
        $this->setEarlyInstance(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager::class, $commandManager);
        Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager::class, $commandManager);
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
     * Require libraries, in case TYPO3 is in non composer mode
     */
    protected function requireLibraries()
    {
        @include 'phar://' . __DIR__ . '/../../Libraries/symfony-process.phar/vendor/autoload.php';
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
        if (!self::usesComposerClassLoading() && !class_exists(\Helhum\Typo3Console\Package\UncachedPackageManager::class)) {
            require __DIR__ . '/../Package/UncachedPackageManager.php';
        }
        $packageManager = new \Helhum\Typo3Console\Package\UncachedPackageManager();
        $this->setEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
        Utility\ExtensionManagementUtility::setPackageManager($packageManager);
        $dependencyResolver = Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\DependencyResolver::class);
        $dependencyResolver->injectDependencyOrderingService(
            Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\DependencyOrderingService::class)
        );
        $packageManager->injectDependencyResolver($dependencyResolver);
        $packageManager->init($this);
        Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
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
                'groups' => []
            ];
        }
    }

    public function initializeConfigurationManagement()
    {
        $this->populateLocalConfiguration();
        $this->setDefaultTimezone();
        $this->defineUserAgentConstant();
    }

    public function initializeDatabaseConnection()
    {
        // @deprecated can be removed if TYPO3 7 support is removed
        if (is_callable([$this, 'defineDatabaseConstants'])) {
            $this->defineDatabaseConstants();
        }
        $this->initializeTypo3DbGlobal();
    }

    protected function flushOutputBuffers()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();
    }

    /**
     * Sets up additional configuration applied in all scopes
     *
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function applyAdditionalConfigurationSettings()
    {
        $this->setFinalCachingFrameworkCacheConfiguration()
            ->defineLoggingAndExceptionConstants()
            ->unsetReservedGlobalVariables();
        return $this;
    }
}
