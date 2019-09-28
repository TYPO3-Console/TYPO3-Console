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
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Trait for use with extension commands
 */
trait SetupExtensionsTrait
{
    /**
     * @var InstallUtility
     */
    private $extensionInstaller;

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
            $this->output->writeln(sprintf('<info>Extension "%s" is now set up.</info>', $extensionKeysAsString));
        } else {
            $this->output->writeln(sprintf('<info>Extensions "%s" are now set up.</info>', $extensionKeysAsString));
        }

        if ($verbose) {
            $this->output->writeln('');
            $extensionSetupResultRenderer->renderSchemaResult($this->output);
            $extensionSetupResultRenderer->renderExtensionDataImportResult($this->output);
            $extensionSetupResultRenderer->renderExtensionFileImportResult($this->output);
            $extensionSetupResultRenderer->renderImportedStaticDataResult($this->output);
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
}
