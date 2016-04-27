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
     * @var \Helhum\Typo3Console\Service\Configuration\ConfigurationService
     * @inject
     */
    protected $configurationService;

    /**
     * @var \Helhum\Typo3Console\Service\Configuration\ConsoleRenderer\ConsoleRenderer
     * @inject
     */
    protected $consoleRenderer;

    /**
     * Removing system configuration by path
     *
     * Example: ./typo3cms configuration:removebypath DB,EXT/EXTCONF/realurl
     *
     * @param array $paths Path to system configuration that should be removed. Multiple paths can be specified separated by comma
     * @param bool $force If set, do not ask for confirmation
     */
    public function removeCommand(array $paths, $force = false)
    {
        foreach ($paths as $path) {
            if (!$this->configurationService->localIsActive($path)) {
                $this->outputLine('<warning>The configuration path "%s" is overwritten by custom configuration options. Removing from local configuration will have no effect.</warning>', array($path));
            }
            if (!$force && $this->configurationService->hasLocal($path)) {
                $reallyDelete = $this->output->askConfirmation('Remove ' . $path . ' from system configuration (TYPO3_CONF_VARS)? (yes/<b>no</b>): ', false);
                if (!$reallyDelete) {
                    continue;
                }
            }
            $removed = $this->configurationService->removeLocal($path);
            if ($removed) {
                $this->outputLine('<info>Removed "%s" from system configuration</info>', array($path));
            } else {
                $this->outputLine('<warning>Path "%s" seems invalid or empty. Nothing done!</warning>', array($path));
            }
        }
    }

    /**
     * Show system configuration by path
     *
     * Example: ./typo3cms configuration:show DB
     *
     * @param string $path Path to system configuration.
     */
    public function showCommand($path)
    {
        if (!$this->configurationService->hasActive($path) && !$this->configurationService->hasLocal($path)) {
            $this->outputLine('<error>No configuration found for path "%s"</error>', array($path));
            $this->quit(1);
        }
        if ($this->configurationService->localIsActive($path) && $this->configurationService->hasActive($path)) {
            $active = $this->configurationService->getActive($path);
            $this->outputLine($this->consoleRenderer->render($active));
        } else {
            $local = null;
            $active = null;
            if ($this->configurationService->hasLocal($path)) {
                $local = $this->configurationService->getLocal($path);
            }
            if ($this->configurationService->hasActive($path)) {
                $active = $this->configurationService->getActive($path);
            }
            $this->outputLine($this->consoleRenderer->renderDiff($local, $active));
        }
    }

    /**
     * Show active system configuration by path
     *
     * Example: ./typo3cms configuration:showActive DB
     *
     * @param string $path Path to system configuration.
     */
    public function showActiveCommand($path)
    {
        if (!$this->configurationService->hasActive($path)) {
            $this->outputLine('<error>No configuration found for path "%s"</error>', array($path));
            $this->quit(1);
        }
        $active = $this->configurationService->getActive($path);
        $this->outputLine($this->consoleRenderer->render($active));
    }

    /**
     * Show active system configuration by path
     *
     * Example: ./typo3cms configuration:showActive DB
     *
     * @param string $path Path to system configuration.
     */
    public function showLocalCommand($path)
    {
        if (!$this->configurationService->hasLocal($path)) {
            $this->outputLine('<error>No configuration found for path "%s"</error>', array($path));
            $this->quit(1);
        }
        $active = $this->configurationService->getLocal($path);
        $this->outputLine($this->consoleRenderer->render($active));
    }

    /**
     * Set system configuration by path.
     *
     * Example: ./typo3cms configuration:set DB/extTablesDefinitionScript extTables.php
     *
     * @param string $path Path to system configuration.
     * @param string $value Value for system configuration.
     */
    public function setCommand($path, $value)
    {
        if (!$this->configurationService->hasLocal($path) || !$this->configurationService->localIsActive($path)) {
            $this->outputLine('<error>Cannot set local configuration for path "%s"</error>', array($path));
            $this->quit(1);
        }
        if ($this->configurationService->setLocal($path, $value)) {
            $this->outputLine('<info>Successfully set value for path "%s"</info>', array($path));
        } else {
            $this->outputLine('<error>Could not set value "%s" for configuration path "%s"</error>', array($value, $path));
        }
    }
}
