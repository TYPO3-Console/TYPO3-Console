<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Extension\ExtensionSetup;
use Helhum\Typo3Console\Extension\ExtensionSetupResultRenderer;
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\CacheService;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

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
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var InstallUtility
     */
    protected $extensionInstaller;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var CacheService
     */
    protected $cacheService;

    public function __construct(
        Dispatcher $signalSlotDispatcher,
        PackageManager $packageManager,
        CacheService $cacheService
    ) {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
        $this->packageManager = $packageManager;
        $this->cacheService = $cacheService;
    }

    /**
     * Activate extension(s)
     *
     * Activates one or more extensions by key.
     * Marks extensions as active, sets them up and clears caches for every activated extension.
     *
     * This command is deprecated (and hidden) in Composer mode.
     *
     * @param array $extensionKeys Extension keys to activate. Separate multiple extension keys with comma.
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function activateCommand(array $extensionKeys)
    {
        // @deprecated for composer usage in 5.0 will be removed with 6.0
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
        $this->showDeprecationMessageIfApplicable();
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
            $this->cacheService->flush();
            $this->getExtensionInstaller()->reloadCaches();

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
     * This command is deprecated (and hidden) in Composer mode.
     *
     * @param array $extensionKeys Extension keys to deactivate. Separate multiple extension keys with comma.
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function deactivateCommand(array $extensionKeys)
    {
        // @deprecated for composer usage in 5.0 will be removed with 6.0
        $this->showDeprecationMessageIfApplicable();
        foreach ($extensionKeys as $extensionKey) {
            $this->getExtensionInstaller()->uninstall($extensionKey);
        }
        $extensionKeysAsString = implode('", "', $extensionKeys);
        if (count($extensionKeys) === 1) {
            $this->outputLine('<info>Extension "%s" is now inactive.</info>', [$extensionKeysAsString]);
        } else {
            $this->outputLine('<info>Extensions "%s" are now inactive.</info>', [$extensionKeysAsString]);
        }
    }

    private function showDeprecationMessageIfApplicable()
    {
        if (CompatibilityScripts::isComposerMode()) {
            $this->output->outputLine('<warning>This command is deprecated when TYPO3 is composer managed.</warning>');
            $this->output->outputLine('<warning>It might lead to unexpected results.</warning>');
            $this->output->outputLine('<warning>The PackageStates.php file that tracks which extension should be active,</warning>');
            $this->output->outputLine('<warning>should be generated automatically using install:generatepackagestates.</warning>');
            $this->output->outputLine('<warning>To set up all active extensions, extension:setupactive should be used.</warning>');
            $this->output->outputLine('<warning>This command will be disabled, when TYPO3 is composer managed, in TYPO3 Console 6</warning>');
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
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
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
            $this->getExtensionInstaller()
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
     */
    public function setupActiveCommand()
    {
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
        $this->setupExtensions($this->packageManager->getActivePackages(), $verbose);
    }

    /**
     * Removes all extensions that are not marked as active
     *
     * Directories of inactive extension are <comment>removed</comment> from <code>typo3/sysext</code> and <code>typo3conf/ext</code>.
     * This is a one way command with no way back. Don't blame anybody if this command destroys your data.
     * <comment>Handle with care!</comment>
     *
     * This command is deprecated.
     * Instead of adding extensions and then removing them, just don't add them in the first place.
     *
     * @param bool $force The option has to be specified, otherwise nothing happens
     */
    public function removeInactiveCommand($force = false)
    {
        $this->outputLine('<warning>This command is deprecated and will be removed with TYPO3 Console 6.0</warning>');
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
     * This command is only available in non composer mode.
     */
    public function dumpAutoloadCommand()
    {
        ClassLoadingInformation::dumpClassLoadingInformation();
        $this->output->outputLine('Class Loading information has been updated.');
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
     * @return InstallUtility
     */
    private function getExtensionInstaller(): InstallUtility
    {
        if ($this->extensionInstaller === null) {
            $this->extensionInstaller = $this->objectManager->get(InstallUtility::class);
        }

        return $this->extensionInstaller;
    }

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }
}
