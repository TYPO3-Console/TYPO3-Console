<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Extension;

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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Helhum\Typo3Console\Service\Database\SchemaService;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

class ExtensionSetup
{
    /**
     * @var ExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var InstallUtility
     */
    private $extensionInstaller;

    /**
     * @var SchemaService
     */
    private $schemaService;

    /**
     * @var ExtensionConfiguration
     */
    private $extensionConfiguration;

    public function __construct(
        ExtensionFactory $extensionFactory = null,
        InstallUtility $extensionInstaller = null,
        SchemaService $schemaService = null,
        ExtensionConfiguration $extensionConfiguration = null
    ) {
        $this->extensionFactory = $extensionFactory ?: new ExtensionFactory(GeneralUtility::makeInstance(PackageManager::class));
        $this->extensionInstaller = $extensionInstaller ?: GeneralUtility::makeInstance(ObjectManager::class)->get(InstallUtility::class);
        $this->schemaService = $schemaService ?: GeneralUtility::makeInstance(ObjectManager::class)->get(SchemaService::class);
        $this->extensionConfiguration = $extensionConfiguration ?: new ExtensionConfiguration();
    }

    /**
     * Performs all necessary operations to integrate extensions into the system.
     * Instead of using buggy TYPO3 API, we created our own instead.
     * This might be removed, once the bug is fixed in TYPO3.
     *
     * @param PackageInterface[] $packages
     */
    public function setupExtensions(array $packages)
    {
        foreach ($packages as $package) {
            $this->extensionFactory->getExtensionStructure($package)->fix();
            $this->callInstaller('importInitialFiles', [PathUtility::stripPathSitePrefix($package->getPackagePath()), $package->getPackageKey()]);
            $this->extensionConfiguration->saveDefaultConfiguration($package->getPackageKey());
        }

        $this->schemaService->updateSchema(SchemaUpdateType::expandSchemaUpdateTypes(['safe']));

        foreach ($packages as $package) {
            $relativeExtensionPath = PathUtility::stripPathSitePrefix($package->getPackagePath());
            $extensionKey = $package->getPackageKey();
            $this->callInstaller('importStaticSqlFile', [$relativeExtensionPath]);
            $this->callInstaller('importT3DFile', [$relativeExtensionPath]);
            $this->callInstaller('emitAfterExtensionInstallSignal', [$extensionKey]);
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private function callInstaller($method, array $arguments)
    {
        $installer = $this->extensionInstaller;
        \Closure::bind(function () use ($installer, $method, $arguments) {
            return $installer->$method(...$arguments);
        }, null, $installer)();
    }
}
