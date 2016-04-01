<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * CommandController for working with extension management through CLI/scheduler
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
     * Activates one or more extensions by key
     *
     * The extension files must be present in one of the
     * recognised extension folder paths in TYPO3.
     *
     * @param array $extensionKeys
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
     * Deactivates one or more extensions by key
     *
     * The extension files must be present in one of the
     * recognised extension folder paths in TYPO3.
     *
     * @param array $extensionKeys
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
     * Updates class loading information.
     *
     * @return void
     * @internal
     * @deprecated use dumpautoload instead
     */
    public function dumpClassLoadingInformationCommand()
    {
        if (Bootstrap::usesComposerClassLoading()) {
            $this->output->outputLine('<error>Class loading information is managed by composer. Use "composer dump-autoload" command to update the information.</error>');
            $this->quit(1);
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $this->output->outputLine('Class Loading information has been updated.');
        }
    }

    /**
     * Installs an extension by key
     *
     * @param string $extensionKey
     * @return void
     * @internal
     * @deprecated use activate instead
     */
    public function installCommand($extensionKey)
    {
        $this->activateCommand([$extensionKey]);
    }

    /**
     * Uninstalls an extension by key
     *
     * @param string $extensionKey
     * @return void
     * @internal
     * @deprecated use deactivate instead
     */
    public function uninstallCommand($extensionKey)
    {
        $this->deactivateCommand([$extensionKey]);
    }
}
