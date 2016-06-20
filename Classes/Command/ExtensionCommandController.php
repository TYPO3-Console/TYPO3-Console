<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;

/**
 * CommandController for working with extension management through CLI
 */
class ExtensionCommandController extends CommandController
{
    /**
     * @var bool
     */
    protected $requestAdminPermissions = true;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
     */
    protected $extensionInstaller;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $packageManager;

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $extensionInstaller
     */
    public function injectExtensionInstaller(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $extensionInstaller)
    {
        $this->extensionInstaller = $extensionInstaller;
    }

    /**
     * @param \TYPO3\CMS\Core\Package\PackageManager $packageManager
     */
    public function injectPackageManager(\TYPO3\CMS\Core\Package\PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Activate extension(s).
     *
     * Activates one or more extensions by key.
     *
     * The extension files must be present in one of the
     * recognised extension folder paths in TYPO3.
     *
     * @param array $extensionKeys Array of extension keys to activate, on CLI specified as a list of CSV values
     */
    public function activateCommand(array $extensionKeys)
    {
        $this->emitPackagesMayHaveChangedSignal();
        foreach ($extensionKeys as $extensionKey) {
            $this->extensionInstaller->install($extensionKey);
        }
        $extensionKeysAsString = implode('", "', $extensionKeys);
        if (count($extensionKeys) === 1) {
            $this->outputLine('<info>Extension "%s" is now active.</info>', [$extensionKeysAsString]);
        } else {
            $this->outputLine('<info>Extensions "%s" are now active.</info>', [$extensionKeysAsString]);
        }
    }

    /**
     * Deactivate extension(s)
     *
     * Deactivates one or more extensions by key
     *
     * The extension files must be present in one of the
     * recognised extension folder paths in TYPO3.
     *
     * @param array $extensionKeys Array of extension keys to deactivate, on CLI specified as a list of CSV values
     * @return void
     *
     */
    public function deactivateCommand(array $extensionKeys)
    {
        foreach ($extensionKeys as $extensionKey) {
            $this->extensionInstaller->uninstall($extensionKey);
        }
        $extensionKeysAsString = implode('", "', $extensionKeys);
        if (count($extensionKeys) === 1) {
            $this->outputLine('<info>Extension "%s" is now inactive.</info>', [$extensionKeysAsString]);
        } else {
            $this->outputLine('<info>Extensions "%s" are now inactive.</info>', [$extensionKeysAsString]);
        }
    }

    /**
     * Setup extension(s)
     *
     * Sets up one or more extensions by key.
     *
     * Set up means:
     * * Database migrations and additions
     * * Importing files and data
     * * Writing default extension configuration
     *
     * @param array $extensionKeys
     */
    public function setupCommand(array $extensionKeys)
    {
        foreach ($extensionKeys as $extensionKey) {
            $this->extensionInstaller->processExtensionSetup($extensionKey);
        }
        $extensionKeysAsString = implode('", "', $extensionKeys);
        if (count($extensionKeys) === 1) {
            $this->outputLine('<info>Extension "%s" is now set up.</info>', [$extensionKeysAsString]);
        } else {
            $this->outputLine('<info>Extensions "%s" are now set up.</info>', [$extensionKeysAsString]);
        }
    }

    /**
     * Set up all active extensions
     *
     * Sets up all extension that are active and not part of typo3/cms package
     */
    public function setupActiveCommand()
    {
        $activeExtensions = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $activeExtensions[] = $package->getPackageKey();
        }
        $this->setupCommand($activeExtensions);
    }

    /**
     * Dump class auto-load
     *
     * Updates class loading information.
     *
     * This command is only needed during development. The extension manager takes care
     * creating or updating this info properly during extension (de-)activation.
     */
    public function dumpAutoloadCommand()
    {
        $this->dumpClassLoadingInformationCommand();
    }

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }

    /**
     * Dump class auto-load (DEPRECATED)
     *
     * Updates class loading information.
     * Use "dumpautoload" instead!
     *
     * @return void
     * @internal
     * @deprecated use dumpautoload instead
     * @see typo3_console:extension:dumpautoload
     */
    public function dumpClassLoadingInformationCommand()
    {
        $this->outputLine('<comment>This command is deprecated. Please use <code>./typo3cms extension:dumpautoload</code> instead!</comment>');
        $this->dumpAutoloadCommand();
    }

    /**
     * Install extension (DEPRECATED)
     *
     * Installs an extension by key
     * Use "activate" command instead!
     *
     * @param string $extensionKey The "extension_key" format of extension key to be installed
     * @return void
     * @internal
     * @deprecated use activate instead
     * @see typo3_console:extension:activate
     */
    public function installCommand($extensionKey)
    {
        $this->outputLine('<comment>This command is deprecated. Please use <code>./typo3cms extension:activate</code> instead!</comment>');
        $this->activateCommand([$extensionKey]);
    }

    /**
     * Uninstall extension (DEPRECATED)
     *
     * Uninstalls an extension by key
     * Use "deactivate" command instead!
     *
     * @param string $extensionKey The "extension_key" format of extension key to be uninstalled
     * @return void
     * @internal
     * @deprecated use deactivate instead
     * @see typo3_console:extension:deactivate
     */
    public function uninstallCommand($extensionKey)
    {
        $this->outputLine('<comment>This command is deprecated. Please use <code>./typo3cms extension:deactivate</code> instead!</comment>');
        $this->deactivateCommand([$extensionKey]);
    }
}
