<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Extension;

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
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Convenience object to handle extension state switches from commands
 */
class ExtensionStateCommandsHelper
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var ExtensionSetupResultRenderer
     */
    private $extensionSetupResultRenderer;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var ExtensionSetup
     */
    private $extensionSetup;

    public function __construct(
        OutputInterface $output,
        ExtensionSetup $extensionSetup = null,
        PackageManager $packageManager = null,
        ExtensionSetupResultRenderer $extensionSetupResultRenderer = null
    ) {
        // @deprecated. should be changed to OutputStyle instead
        $this->output = new ConsoleOutput($output);
        $this->extensionSetup = $extensionSetup ?? new ExtensionSetup();
        $this->packageManager = $packageManager ?? GeneralUtility::makeInstance(PackageManager::class);
        $this->extensionSetupResultRenderer = $extensionSetupResultRenderer ?? new ExtensionSetupResultRenderer(GeneralUtility::makeInstance(Dispatcher::class));
    }

    /**
     * Performs all necessary operations to integrate an extension into the system.
     * To do so, we avoid buggy TYPO3 API and use our own instead.
     *
     * @param string[] $extensionKeys
     */
    public function activateExtensions(array $extensionKeys)
    {
        $this->packageManager->scanAvailablePackages();
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
            $this->extensionSetup->updateCaches();

            $extensionKeysAsString = implode('", "', $activatedExtensions);
            if (count($activatedExtensions) === 1) {
                $this->output->outputLine(sprintf('<info>Extension "%s" is now active.</info>', $extensionKeysAsString));
            } else {
                $this->output->outputLine(sprintf('<info>Extensions "%s" are now active.</info>', $extensionKeysAsString));
            }
        }

        if (!empty($extensionsToSetUp)) {
            $this->setupPackages($extensionsToSetUp);
        }
    }

    /**
     * Performs all necessary operations to integrate an extension into the system.
     * To do so, we avoid buggy TYPO3 API and use our own instead.
     *
     * @param string[] $extensionKeys
     */
    public function deactivateExtensions(array $extensionKeys)
    {
        $this->extensionSetup->deactivateExtensions($extensionKeys);
        $extensionKeysAsString = implode('", "', $extensionKeys);
        if (count($extensionKeys) === 1) {
            $this->output->outputLine(sprintf('<info>Extension "%s" is now inactive.</info>', $extensionKeysAsString));
        } else {
            $this->output->outputLine(sprintf('<info>Extensions "%s" are now inactive.</info>', $extensionKeysAsString));
        }
    }

    /**
     * Performs all necessary operations to integrate an extension into the system.
     * To do so, we avoid buggy TYPO3 API and use our own instead.
     */
    public function setupActiveExtensions()
    {
        $this->setupPackages($this->packageManager->getActivePackages());
    }

    /**
     * Performs all necessary operations to integrate an extension into the system.
     * To do so, we avoid buggy TYPO3 API and use our own instead.
     *
     * @param string[] $extensionKeys
     */
    public function setupExtensions(array $extensionKeys)
    {
        $packages = array_map(
            function ($extensionKey) {
                return $this->packageManager->getPackage($extensionKey);
            },
            $extensionKeys
        );
        $this->setupPackages($packages);
    }

    /**
     * Performs all necessary operations to integrate an extension into the system.
     * To do so, we avoid buggy TYPO3 API and use our own instead.
     *
     * @param PackageInterface[] $packages
     */
    private function setupPackages(array $packages)
    {
        $this->extensionSetup->setupExtensions($packages);
        $extensionKeysAsString = implode('", "', array_map(function (PackageInterface $package) {
            return $package->getPackageKey();
        }, $packages));

        if (count($packages) === 1) {
            $this->output->outputLine(sprintf('<info>Extension "%s" is now set up.</info>', $extensionKeysAsString));
        } else {
            $this->output->outputLine(sprintf('<info>Extensions "%s" are now set up.</info>', $extensionKeysAsString));
        }

        if ($this->output->getSymfonyConsoleOutput()->isVerbose()) {
            $this->output->outputLine('');
            $this->extensionSetupResultRenderer->renderSchemaResult($this->output);
            $this->extensionSetupResultRenderer->renderExtensionDataImportResult($this->output);
            $this->extensionSetupResultRenderer->renderExtensionFileImportResult($this->output);
            $this->extensionSetupResultRenderer->renderImportedStaticDataResult($this->output);
        }
    }
}
