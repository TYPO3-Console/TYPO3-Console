<?php
namespace Helhum\Typo3Console;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\ConsoleBootstrap as Bootstrap;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Class Package
 */
class Package extends \TYPO3\CMS\Core\Package\Package
{
    /**
     * @var string
     */
    protected $namespace = 'Helhum\\Typo3Console';

    /**
     * @param PackageManager $packageManager
     * @param string $packageKey
     * @param string $packagePath
     * @param string|null $classesPath
     * @param string $manifestPath
     */
    public function __construct(PackageManager $packageManager, $packageKey, $packagePath, $classesPath = null, $manifestPath = '')
    {
        \TYPO3\CMS\Core\Package\Package::__construct($packageManager, $packageKey, $packagePath, $classesPath, $manifestPath);
        if (!file_exists(PATH_site . 'typo3conf/PackageStates.php')) {
            // Force loading of the console in case we do not have a package states file yet (pre-install)
            $this->protected = true;
        }
    }

    /**
     * Register the cli request handler only when in cli mode
     *
     * @param Bootstrap $bootstrap
     */
    public function bootPackage(Bootstrap $bootstrap)
    {
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
            $bootstrap->registerRequestHandler(new RequestHandler($bootstrap));
            $this->registerCommands($bootstrap);
        }
    }

    /**
     * @param Bootstrap $bootstrap
     */
    protected function registerCommands(Bootstrap $bootstrap)
    {
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\CacheCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\BackendCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\SchedulerCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\CleanupCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\DocumentationCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\InstallCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\DatabaseCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\ConfigurationCommandController::class);
        $bootstrap->getCommandManager()->registerCommandController(\Helhum\Typo3Console\Command\FrontendCommandController::class);

        $bootstrap->setRunLevelForCommand('typo3_console:install:databasedata', RunLevel::LEVEL_MINIMAL);
        $bootstrap->addBootingStepForCommand('typo3_console:install:databasedata', 'helhum.typo3console:database');
        $bootstrap->addBootingStepForCommand('typo3_console:install:databasedata', 'helhum.typo3console:enablecorecaches');
        $bootstrap->setRunLevelForCommand('typo3_console:install:defaultconfiguration', RunLevel::LEVEL_MINIMAL);
        $bootstrap->addBootingStepForCommand('typo3_console:install:defaultconfiguration', 'helhum.typo3console:database');
        $bootstrap->setRunLevelForCommand('typo3_console:install:*', RunLevel::LEVEL_COMPILE);

        $bootstrap->setRunLevelForCommand('typo3_console:cache:flush', RunLevel::LEVEL_COMPILE);
        $bootstrap->addBootingStepForCommand('typo3_console:cache:flush', 'helhum.typo3console:database');

        $bootstrap->setRunLevelForCommand('typo3_console:backend:*', RunLevel::LEVEL_MINIMAL);
    }
}
