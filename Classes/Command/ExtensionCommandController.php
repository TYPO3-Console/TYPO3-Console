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
     * @inject
     */
    protected $signalSlotDispatcher;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
     * @inject
     */
    protected $extensionInstaller;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     * @inject
     */
    protected $packageManager;

    /**
     * Activate extension(s)
     *
     * Activates one or more extensions by key.
     * Marks extensions as active, sets them up and clears caches for every activated extension.
     *
     * @param array $extensionKeys Extension keys to activate. Separate multiple extension keys with comma.
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
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
     * Deactivates one or more extensions by key.
     * Marks extensions as inactive in the system and clears caches for every deactivated extension.
     *
     * @param array $extensionKeys Extension keys to deactivate. Separate multiple extension keys with comma.
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
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
     * Set up extension(s)
     *
     * Sets up one or more extensions by key.
     * Set up means:
     *
     * - Database migrations and additions
     * - Importing files and data
     * - Writing default extension configuration
     *
     * @param array $extensionKeys Extension keys to set up. Separate multiple extension keys with comma.
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
     * Sets up all extensions that are marked as active in the system.
     *
     * This command is especially useful for deployment, where extensions
     * are already marked as active, but have not been set up yet or might have changed. It ensures every necessary
     * setup step for the (changed) extensions is performed.
     * As an additional benefit no caches are flushed, which significantly improves performance of this command
     * and avoids unnecessary cache clearing.
     *
     * @see extensionmanager:extension:setup
     * @see typo3_console:install:generatepackagestates
     * @see typo3_console:cache:flush
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
     * Updates class loading information in non composer managed TYPO3 installations.
     *
     * This command is only needed during development. The extension manager takes care
     * creating or updating this info properly during extension (de-)activation.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function dumpAutoloadCommand()
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
     * List extensions that are available in the system
     *
     * @param bool $active Only show active extensions
     * @param bool $inactive Only show inactive extensions
     * @param bool $raw Enable machine readable output (just extension keys separated by line feed)
     */
    public function listCommand($active = false, $inactive = false, $raw = false)
    {
        $extensionInformation = [];
        if (!$active || $inactive) {
            $this->emitPackagesMayHaveChangedSignal();
            $packages = $this->packageManager->getAvailablePackages();
        } else {
            $packages = $this->packageManager->getActivePackages();
        }
        foreach ($packages as $package) {
            if ($inactive && $this->packageManager->isPackageActive($package->getPackageKey())) {
                continue;
            }
            $metaData = $package->getPackageMetaData();
            $extensionInformation[] = [
                'package_key' => $package->getPackageKey(),
                'version' => $metaData->getVersion(),
                'description' => $metaData->getDescription(),
            ];
        }
        if ($raw) {
            $this->outputLine('%s', [implode(PHP_EOL, array_column($extensionInformation, 'package_key'))]);
        } else {
            $this->output->outputTable(
                $extensionInformation,
                ['Package key', 'Version', 'Description']
            );
        }
    }

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }
}
