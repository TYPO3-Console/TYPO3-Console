<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ConfigurationCommandController
 */
class ConfigurationCommandController extends CommandController implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

    /**
     * Removing system configuration by path
     *
     * Example: ./typo3cms configuration:removebypath DB,EXT/EXTCONF/realurl
     *
     * @param array $paths Path to system configuration that should be removed. Multiple paths can be specified separated by comma
     * @param bool $force If set, do not ask for confirmation
     */
    public function removeByPathCommand(array $paths, $force = false)
    {
        if (!$force) {
            do {
                $answer = strtolower($this->output->ask('Remove ' . implode(',', $paths) . ' from system configuration (TYPO3_CONF_VARS)? (y/N): '));
            } while ($answer !== 'y' && $answer !== 'yes');
        }
        $removed = $this->configurationManager->removeLocalConfigurationKeysByPath($paths);
        if (!$removed) {
            $this->outputLine('Paths seems invalid or empty. Nothing done!');
            $this->sendAndExit(1);
        }
        $this->outputLine('Removed from system configuration');
    }
}
