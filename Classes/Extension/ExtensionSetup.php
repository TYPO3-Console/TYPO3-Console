<?php
namespace Helhum\Typo3Console\Extension;

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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Helhum\Typo3Console\Service\Database\SchemaService;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Class ExtensionSetup
 */
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

    public function __construct(
        ExtensionFactory $extensionFactory = null,
        InstallUtility $extensionInstaller = null,
        SchemaService $schemaService = null
    ) {
        $this->extensionFactory = $extensionFactory ?: new ExtensionFactory(GeneralUtility::makeInstance(PackageManager::class));
        $this->extensionInstaller = $extensionInstaller ?: GeneralUtility::makeInstance(ObjectManager::class)->get(InstallUtility::class);
        $this->schemaService = $schemaService ?: GeneralUtility::makeInstance(ObjectManager::class)->get(SchemaService::class);
    }

    /**
     * @param PackageInterface[] $packages
     */
    public function setupExtensions(array $packages)
    {
        foreach ($packages as $package) {
            $this->extensionFactory->getExtensionStructure($package)->fix();
            $this->callInstaller('importInitialFiles', [PathUtility::stripPathSitePrefix($package->getPackagePath()), $package->getPackageKey()]);
            $this->callInstaller('saveDefaultConfiguration', [$package->getPackageKey()]);
        }

        $this->schemaService->updateSchema(SchemaUpdateType::expandSchemaUpdateTypes(['safe']));

        foreach ($packages as $package) {
            $this->callInstaller('importStaticSqlFile', [PathUtility::stripPathSitePrefix($package->getPackagePath())]);
            $this->callInstaller('importT3DFile', [PathUtility::stripPathSitePrefix($package->getPackagePath())]);
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    private function callInstaller($method, array $arguments)
    {
        $installer = $this->extensionInstaller;
        call_user_func(
         \Closure::bind(function () use ($installer, $method, $arguments) {
             return call_user_func_array([$installer, $method], $arguments);
         }, null, InstallUtility::class));
    }
}
