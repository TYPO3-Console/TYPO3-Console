<?php
namespace Helhum\Typo3Console;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;

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
     * @param \TYPO3\Flow\Package\PackageManager $packageManager
     * @param string $packageKey
     * @param string $packagePath
     * @param string|null $classesPath
     * @param string $manifestPath
     */
    public function __construct(\TYPO3\Flow\Package\PackageManager $packageManager, $packageKey, $packagePath, $classesPath = null, $manifestPath = '')
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
     * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
     */
    public function bootPackage(\TYPO3\Flow\Core\Bootstrap $bootstrap)
    {
        if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) && is_callable(array($bootstrap, 'registerRequestHandler'))) {
            $bootstrap->registerRequestHandler(new RequestHandler($bootstrap));
            $this->registerCommands($bootstrap);
        }
    }

    /**
     * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
     */
    protected function registerCommands(\TYPO3\Flow\Core\Bootstrap $bootstrap)
    {
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\CacheCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\BackendCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\SchedulerCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\CleanupCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\DocumentationCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\InstallCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\DatabaseCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\ConfigurationCommandController');
        $bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\FrontendCommandController');

        $bootstrap->setRunLevelForCommand('typo3_console:install:databasedata', RunLevel::LEVEL_MINIMAL);
        $bootstrap->addBootingStepForCommand('typo3_console:install:databasedata', 'helhum.typo3console:database');
        $bootstrap->setRunLevelForCommand('typo3_console:install:defaultconfiguration', RunLevel::LEVEL_MINIMAL);
        $bootstrap->addBootingStepForCommand('typo3_console:install:defaultconfiguration', 'helhum.typo3console:database');
        $bootstrap->setRunLevelForCommand('typo3_console:install:*', RunLevel::LEVEL_COMPILE);

        $bootstrap->setRunLevelForCommand('typo3_console:cache:flush', RunLevel::LEVEL_COMPILE);
        $bootstrap->addBootingStepForCommand('typo3_console:cache:flush', 'helhum.typo3console:database');

        $bootstrap->setRunLevelForCommand('typo3_console:backend:*', RunLevel::LEVEL_MINIMAL);
    }
}
