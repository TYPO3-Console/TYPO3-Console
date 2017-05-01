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

use Helhum\Typo3Console\Extension\ExtensionSetup;
use Helhum\Typo3Console\Extension\ExtensionSetupResultRenderer;
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
     * @var \Helhum\Typo3Console\Service\CacheService
     * @inject
     */
    protected $cacheService;

    /**
     * Activate extension(s)
     *
     * Activates one or more extensions by key.
     * Marks extensions as active, sets them up and clears caches for every activated extension.
     *
     * @param array $extensionKeys Extension keys to activate. Separate multiple extension keys with comma.
     * @param bool $verbose Whether or not to output results
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function activateCommand(array $extensionKeys, $verbose = false)
    {
        if (getenv('TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES') && Bootstrap::usesComposerClassLoading()) {
            $this->output->outputLine('<warning>This command has been deprecated to be used in composer mode, as it might lead to unexpected results</warning>');
            $this->output->outputLine('<warning>The PackageStates.php file that tracks which extension should be active,</warning>');
            $this->output->outputLine('<warning>is now generated automatically when installing the console with composer.</warning>');
            $this->output->outputLine('<warning>To set up all active extensions correctly, please use the extension:setupactive command</warning>');
        }

        $this->emitPackagesMayHaveChangedSignal();
        $activatedExtensions = [];
        $extensionsToSetUp = [];
        foreach ($extensionKeys as $extensionKey) {
            $extensionsToSetUp[] = $this->packageManager->getPackage($extensionKey);
            if (!$this->packageManager->isPackageActive($extensionKey)) {
                $this->packageManager->activatePackage($extensionKey);
                $activatedExtensions[] = $extensionKey;
            }
        }

        if (!empty($activatedExtensions)) {
            $this->extensionInstaller->reloadCaches();
            $this->cacheService->flush();

            $extensionKeysAsString = implode('", "', $activatedExtensions);
            if (count($activatedExtensions) === 1) {
                $this->outputLine('<info>Extension "%s" is now active.</info>', [$extensionKeysAsString]);
            } else {
                $this->outputLine('<info>Extensions "%s" are now active.</info>', [$extensionKeysAsString]);
            }
        }

        if (!empty($extensionsToSetUp)) {
            $this->setupExtensions($extensionsToSetUp, $verbose);
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
        if (getenv('TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES') && Bootstrap::usesComposerClassLoading()) {
            $this->output->outputLine('<warning>This command has been deprecated to be used in composer mode, as it might lead to unexpected results</warning>');
            $this->output->outputLine('<warning>The PackageStates.php file that tracks which extension should be active,</warning>');
            $this->output->outputLine('<warning>is now generated automatically when installing the console with composer.</warning>');
            $this->output->outputLine('<warning>To set up all active extensions correctly, please use the extension:setupactive command</warning>');
        }

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
     * @param bool $verbose Whether or not to output results
     */
    public function setupCommand(array $extensionKeys, $verbose = false)
    {
        $packages = [];
        foreach ($extensionKeys as $extensionKey) {
            $packages[] = $this->packageManager->getPackage($extensionKey);
        }
        $this->setupExtensions($packages, $verbose);
    }

    /**
     * Performs all necessary operations to integrate an extension into the system.
     * To do so, we avoid buggy TYPO3 API and use our own instead.
     *
     * @param PackageInterface[] $packages
     * @param bool $verbose Whether or not to output results
     */
    private function setupExtensions(array $packages, $verbose = false)
    {
        $extensionSetupResultRenderer = new ExtensionSetupResultRenderer($this->signalSlotDispatcher);

        $extensionSetup = new ExtensionSetup(
            new ExtensionFactory($this->packageManager),
            $this->extensionInstaller
        );

        $extensionSetup->setupExtensions($packages);
        $extensionKeysAsString = implode('", "', array_map(function (PackageInterface $package) {
            return $package->getPackageKey();
        }, $packages));
        if (count($packages) === 1) {
            $this->outputLine('<info>Extension "%s" is now set up.</info>', [$extensionKeysAsString]);
        } else {
            $this->outputLine('<info>Extensions "%s" are now set up.</info>', [$extensionKeysAsString]);
        }

        if ($verbose) {
            $this->outputLine();
            $extensionSetupResultRenderer->renderSchemaResult($this->output);
            $extensionSetupResultRenderer->renderExtensionDataImportResult($this->output);
            $extensionSetupResultRenderer->renderExtensionFileImportResult($this->output);
            $extensionSetupResultRenderer->renderImportedStaticDataResult($this->output);
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
     * @see typo3_console:extension:setup
     * @see typo3_console:install:generatepackagestates
     * @see typo3_console:cache:flush
     *
     * @param bool $verbose Whether or not to output results
     */
    public function setupActiveCommand($verbose = false)
    {
        $this->setupExtensions($this->packageManager->getActivePackages(), $verbose);
    }

    /**
     * Removes all extensions that are not marked as active
     *
     * Directories of inactive extension are <comment>removed</comment> from <code>typo3/sysext</code> and <code>typo3conf/ext</code>.
     * This is a one way command with no way back. Don't blame anybody if this command destroys your data.
     * <comment>Handle with care!</comment>
     *
     * @param bool $force The option has to be specified, otherwise nothing happens
     */
    public function removeInactiveCommand($force = false)
    {
        if ($force) {
            $activePackages = $this->packageManager->getActivePackages();
            $this->packageManager->scanAvailablePackages();
            foreach ($this->packageManager->getAvailablePackages() as $package) {
                if (empty($activePackages[$package->getPackageKey()])) {
                    $this->packageManager->unregisterPackage($package);
                    if (is_dir($package->getPackagePath())) {
                        GeneralUtility::flushDirectory($package->getPackagePath());
                        $removedPaths[] = PathUtility::stripPathSitePrefix($package->getPackagePath());
                    }
                }
            }
            $this->packageManager->forceSortAndSavePackageStates();
            if (!empty($removedPaths)) {
                $this->outputLine('<info>The following directories have been removed:</info>' . chr(10) . implode(chr(10), $removedPaths));
            } else {
                $this->outputLine('<info>Nothing was removed</info>');
            }
        } else {
            $this->outputLine('<warning>Operation not confirmed and has been skipped</warning>');
            $this->quit(1);
        }
    }

    /**
     * Dump class auto-load
     *
     * Updates class loading information in non Composer managed TYPO3 installations.
     *
     * This command is only needed during development. The extension manager takes care
     * creating or updating this info properly during extension (de-)activation.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function dumpAutoloadCommand()
    {
        if (Bootstrap::usesComposerClassLoading()) {
            $this->output->outputLine('<error>Class loading information is managed by Composer. Use "composer dump-autoload" command to update the information.</error>');
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
                ['Extension key', 'Version', 'Description']
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
