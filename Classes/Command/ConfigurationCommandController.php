<?php
namespace Helhum\Typo3Console\Command;

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
     * Remove configuration option
     *
     * Removes a system configuration option by path.
     *
     * For this command to succeed, the configuration option(s) must be in
     * LocalConfiguration.php and not be overridden elsewhere.
     *
     * <b>Example:</b> <code>typo3cms configuration:remove DB,EXT/EXTCONF/realurl</code>
     *
     * @param array $paths Path to system configuration that should be removed. Multiple paths can be specified separated by comma
     * @param bool $force If set, does not ask for confirmation
     */
    public function removeCommand(array $paths, $force = false)
    {
        foreach ($paths as $path) {
            if (!$this->configurationService->localIsActive($path)) {
                $this->outputLine('<warning>It seems that configuration for path "%s" is overridden.</warning>', [$path]);
                $this->outputLine('<warning>Removing the new value might have no effect.</warning>');
            }
            if (!$force && $this->configurationService->hasLocal($path)) {
                $reallyDelete = $this->output->askConfirmation('Remove ' . $path . ' from system configuration (TYPO3_CONF_VARS)? (yes/<b>no</b>): ', false);
                if (!$reallyDelete) {
                    continue;
                }
            }
            $removed = $this->configurationService->removeLocal($path);
            if ($removed) {
                $this->outputLine('<info>Removed "%s" from system configuration.</info>', [$path]);
            } else {
                $this->outputLine('<warning>Path "%s" seems invalid or empty. Nothing done!</warning>', [$path]);
            }
        }
    }

    /**
     * Show configuration value
     *
     * Shows system configuration value by path.
     * If the currently active configuration differs from the value in LocalConfiguration.php
     * the difference between these values is shown.
     *
     * <b>Example:</b> <code>typo3cms configuration:show DB</code>
     *
     * @param string $path Path to system configuration option
     */
    public function showCommand($path)
    {
        $hasActive = $this->configurationService->hasActive($path);
        $hasLocal = $this->configurationService->hasLocal($path);
        if (!$hasActive && !$hasLocal) {
            $this->outputLine('<error>No configuration found for path "%s"</error>', [$path]);
            $this->quit(1);
        }
        $active = null;
        if ($hasActive) {
            $active = $this->configurationService->getActive($path);
        }
        if ($this->configurationService->localIsActive($path) && $hasActive) {
            $this->outputLine($this->consoleRenderer->render($active));
        } else {
            $local = null;
            if ($hasLocal) {
                $local = $this->configurationService->getLocal($path);
            }
            $this->outputLine($this->consoleRenderer->renderDiff($local, $active));
        }
    }

    /**
     * Show active configuration value
     *
     * Shows active system configuration by path.
     * Shows the configuration value that is currently effective, no matter where and how it is set.
     *
     * <b>Example:</b> <code>typo3cms configuration:showActive DB --json</code>
     *
     * @param string $path Path to system configuration
     * @param bool $json If set, the configuration is shown as JSON
     */
    public function showActiveCommand($path, $json = false)
    {
        if (!$this->configurationService->hasActive($path)) {
            $this->outputLine('<error>No configuration found for path "%s"</error>', [$path]);
            $this->quit(1);
        }
        $active = $this->configurationService->getActive($path);
        $this->outputLine($this->consoleRenderer->render($active, $json));
    }

    /**
     * Show local configuration value
     *
     * Shows local configuration option value by path.
     * Shows the value which is stored in LocalConfiguration.php.
     * Note that this value could be overridden. Use <code>typo3cms configuration:show [path]</code> to see if this is the case.
     *
     * <b>Example:</b> <code>typo3cms configuration:showLocal DB</code>
     *
     * @param string $path Path to local system configuration
     * @param bool $json If set, the configuration is shown as JSON
     * @see typo3_console:configuration:show
     */
    public function showLocalCommand($path, $json = false)
    {
        if (!$this->configurationService->hasLocal($path)) {
            $this->outputLine('<error>No configuration found for path "%s"</error>', [$path]);
            $this->quit(1);
        }
        $active = $this->configurationService->getLocal($path);
        $this->outputLine($this->consoleRenderer->render($active, $json));
    }

    /**
     * Set configuration value
     *
     * Set system configuration option value by path.
     *
     * <b>Example:</b> <code>typo3cms configuration:set SYS/fileCreateMask 0664</code>
     *
     * @param string $path Path to system configuration
     * @param string $value Value for system configuration
     */
    public function setCommand($path, $value)
    {
        if (!$this->configurationService->localIsActive($path)) {
            $this->outputLine('<warning>It seems that configuration for path "%s" is overridden.</warning>', [$path]);
            $this->outputLine('<warning>Writing the new value might have no effect.</warning>');
        }

        $success = $this->configurationService->setLocal($path, $value);

        if (!$this->configurationService->hasLocal($path)) {
            $this->outputLine('<warning>Value "%s" for configuration path "%s" is still empty.</warning>', [$value, $path]);
            $this->outputLine('<warning>Maybe it is removed in AdditionalConfiguration.php?</warning>');
        }

        if ($success) {
            $this->outputLine('<info>Successfully set value for path "%s".</info>', [$path]);
        } else {
            $this->outputLine('<warning>Could not set value "%s" for configuration path "%s".</warning>', [$value, $path]);
        }
    }
}
