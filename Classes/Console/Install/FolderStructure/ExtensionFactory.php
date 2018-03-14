<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\FolderStructure;

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

use Helhum\Typo3Console\Package\UncachedPackageManager;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\CMS\Install\FolderStructure\StructureFacade;

/**
 * Factory returns folder structure object hierarchy
 * for TYPO3 core and Extensions
 */
class ExtensionFactory extends DefaultFactory
{
    /**
     * @var UncachedPackageManager
     */
    private $packageManager;

    /**
     * ExtensionFactory constructor.
     *
     * @param UncachedPackageManager $packageManager
     */
    public function __construct(UncachedPackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Get default structure object hierarchy
     *
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     * @return StructureFacade
     */
    public function getStructure()
    {
        $structure = $this->getDefaultStructureDefinition();
        $structure['children'] = $this->appendStructureDefinition($structure['children'], $this->createExtensionStructureDefinition($this->packageManager->getActivePackages()));

        return new StructureFacade(new RootNode($structure));
    }

    /**
     * Creates the folder structure for one extension
     *
     * @param PackageInterface $package
     * @return StructureFacade
     */
    public function getExtensionStructure(PackageInterface $package)
    {
        $structure = [
            'name' => substr(PATH_site, 0, -1),
            'targetPermission' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'],
            'children' => $this->appendStructureDefinition([], $this->createExtensionStructureDefinition([$package])),
        ];

        return new StructureFacade(new RootNode($structure));
    }

    /**
     * Default definition of folder and file structure with dynamic
     * permission settings
     *
     * @param PackageInterface[] $packages
     * @return array
     */
    private function createExtensionStructureDefinition(array $packages)
    {
        $structureBase = [];
        foreach ($packages as $package) {
            $extensionConfiguration = $this->packageManager->getExtensionConfiguration($package);

            if (isset($extensionConfiguration['uploadfolder']) && (bool)$extensionConfiguration['uploadfolder']) {
                $structureBase[] = $this->getExtensionUploadDirectory($package->getPackageKey());
            }

            if (!empty($extensionConfiguration['createDirs'])) {
                foreach (explode(',', $extensionConfiguration['createDirs']) as $directoryToCreate) {
                    $absolutePath = GeneralUtility::getFileAbsFileName(trim($directoryToCreate));
                    // Only create valid paths.
                    if (!empty($absolutePath)) {
                        $structureBase[] = $this->getDirectoryNodeByPath(PathUtility::stripPathSitePrefix($absolutePath));
                    }
                }
            }
        }

        return $structureBase;
    }

    /**
     * Extension uploads directory.
     *
     * @param string $extension Extension key
     * @return array
     */
    private function getExtensionUploadDirectory($extension)
    {
        return $this->getDirectoryNodeByPath('uploads/tx_' . str_replace('_', '', $extension));
    }

    /**
     * Build directory nodes by given $path
     *
     * @param string $path Path to directory
     * @return array
     */
    private function getDirectoryNodeByPath($path)
    {
        $baseNode = [];
        $parts = explode('/', $path);
        $node = &$baseNode;
        foreach ($parts as $part) {
            $node[0] = [
                'name' => $part,
                'type' => DirectoryNode::class,
                'targetPermission' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'],
                'children' => [],
            ];
            // Add next directory as children of current node
            $node = &$node[0]['children'];
        }

        return isset($baseNode[0]) ? $baseNode[0] : [];
    }

    /**
     * Append $original structure definition with $additional structure definition.
     * Only add missing nodes.
     *
     * @param array $original
     * @param array $additional
     * @return array
     */
    private function appendStructureDefinition(array $original, array $additional)
    {
        foreach ($additional as $additionalStructure) {
            $structureKey = false;
            foreach ($original as $key => $originalStructure) {
                if ($originalStructure['name'] === $additionalStructure['name']) {
                    $structureKey = $key;
                }
            }

            if ($structureKey === false) {
                // Append key to original
                $original[] = $additionalStructure;
            } else {
                // Append children, if necessary
                if (isset($additionalStructure['children'])) {
                    if (isset($original[$structureKey]['children'])) {
                        $original[$structureKey]['children'] = $this->appendStructureDefinition(
                            $original[$structureKey]['children'],
                            $additionalStructure['children']
                        );
                    } else {
                        $original[$structureKey]['children'] = $additionalStructure['children'];
                    }
                }
            }
        }

        return $original;
    }
}
