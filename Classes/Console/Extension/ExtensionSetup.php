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

use Helhum\Typo3Console\Database\Schema\SchemaUpdate;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Service\CacheService;
use Helhum\Typo3Console\Service\Database\SchemaService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Install\Service\LateBootService;

class ExtensionSetup
{
    /**
     * @var InstallUtility
     */
    private $extensionInstaller;

    /**
     * @var SchemaService
     */
    private $schemaService;
    /**
     * @var ExtensionSetupEventDispatcher
     */
    private $eventDispatcher;

    public function __construct(
        InstallUtility $extensionInstaller = null,
        ExtensionSetupEventDispatcher $eventDispatcher = null,
        SchemaService $schemaService = null
    ) {
        $this->extensionInstaller = $extensionInstaller ?? GeneralUtility::makeInstance(ObjectManager::class)->get(InstallUtility::class);
        $this->eventDispatcher = $eventDispatcher ?? new ExtensionSetupEventDispatcher(GeneralUtility::makeInstance(EventDispatcherInterface::class));
        $this->schemaService = $schemaService ?? new SchemaService(new SchemaUpdate(), $this->eventDispatcher);
    }

    public function updateCaches()
    {
        $cacheService = new CacheService();
        $cacheService->flush();
        $lateBootService = GeneralUtility::makeInstance(LateBootService::class);
        $container = $lateBootService->getContainer();
        $backup = $lateBootService->makeCurrent($container);
        $this->eventDispatcher->updateParentEventDispatcher($container->get(EventDispatcherInterface::class));
        $this->extensionInstaller->reloadCaches();
        $lateBootService->makeCurrent($backup);
    }

    public function deactivateExtensions(array $packageKeys)
    {
        foreach ($packageKeys as $extensionKey) {
            $this->extensionInstaller->uninstall($extensionKey);
        }
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
        $this->extensionInstaller->injectEventDispatcher($this->eventDispatcher);

        $this->schemaService->updateSchema(SchemaUpdateType::expandSchemaUpdateTypes(['safe']));

        foreach ($packages as $package) {
            $this->extensionInstaller->processExtensionSetup($package->getPackageKey());
            $this->eventDispatcher->dispatch(new AfterPackageActivationEvent($package->getPackageKey(), 'typo3-cms-extension', $this->extensionInstaller));
        }
    }
}
