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
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Cli\Response;

/**
 * @internal
 */
class Kernel
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var RunLevel
     */
    private $runLevel;

    public function __construct(\Composer\Autoload\ClassLoader $classLoader)
    {
        $this->ensureRequiredEnvironment();
        $this->bootstrap = Bootstrap::getInstance();
        $this->bootstrap->initializeClassLoader($classLoader);
        $this->initializeNonComposerClassLoading();
        $this->initializeCompatibilityLayer();
        $this->runLevel = new RunLevel();
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

    /**
     * Register auto loading for our own classes in case we cannot rely on composer class loading.
     */
    private function initializeNonComposerClassLoading()
    {
        if ($this->bootstrap::usesComposerClassLoading()) {
            return;
        }
        $classesPaths = [__DIR__ . '/../../Classes', __DIR__ . '/../../Resources/Private/ExtensionArtifacts/src/'];
        $classLoader = new ClassLoader();
        $classLoader->addPsr4('Helhum\\Typo3Console\\', $classesPaths);
        spl_autoload_register(function ($className) use ($classLoader) {
            if ($file = $classLoader->findFile($className)) {
                require $file;
            }
        });
        $pharFile = __DIR__ . '/../../Libraries/symfony-process.phar';
        require 'phar://' . $pharFile . '/vendor/autoload.php';
    }

    /**
     * If detected TYPO3 version does not match the main supported version,
     * overlay compatibility classes for the detected branch, by registering
     * an autoloader and aliasing the compatibility class with the original class name.
     */
    private function initializeCompatibilityLayer()
    {
        $typo3Branch = 8;
        if (!method_exists($this->bootstrap, 'setCacheHashOptions')) {
            $typo3Branch = 9;
        }
        if ($typo3Branch === 8) {
            return;
        }
        $compatibilityClassesPath = __DIR__ . '/../../Compatibility/LTS' . $typo3Branch;
        $compatibilityNamespace = 'Helhum\\Typo3Console\\LTS' . $typo3Branch . '\\';
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
     * Bootstraps the minimal infrastructure, registers a request handler and
     * then passes control over to that request handler.
     *
     * @param InputInterface $input
     * @return Response
     */
    public function handle(InputInterface $input): Response
    {
        $this->bootstrap->setEarlyInstance(RunLevel::class, $this->runLevel);
        $sequence = $this->runLevel->buildSequence(RunLevel::LEVEL_ESSENTIAL);
        $sequence->invoke($this->bootstrap);

        $this->bootstrap->registerRequestHandlerImplementation(RequestHandler::class);
        $this->bootstrap->handleRequest($input);
        return $this->bootstrap->getEarlyInstance(Response::class);
    }

    public function terminate(Response $response)
    {
        $this->bootstrap->shutdown();
        exit($response->getExitCode());
    }
}
