<?php
namespace Helhum\Typo3Console\FolderStructure;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\CMS\Install\FolderStructure\StructureFacade;

/**
 * Factory returns extension folder structure object hierarchy
 */
class ExtensionFactory extends \TYPO3\CMS\Install\FolderStructure\DefaultFactory
{
    /**
     * Get default structure object hierarchy
     *
     * @throws \InvalidArgumentException
     * @return StructureFacade
     */
    public function getStructure()
    {
        $structure = $this->getDefaultStructureDefinition();
        $structure['children'] = $this->appendStructureDefinition($structure['children'], $this->getExtensionStructureDefinition());
        $rootNode = GeneralUtility::makeInstance(RootNode::class, $structure, null);
        return GeneralUtility::makeInstance(StructureFacade::class, $rootNode);
    }

    /**
     * Default definition of folder and file structure with dynamic
     * permission settings
     *
     * @return array
     */
    protected function getExtensionStructureDefinition()
    {
        /** @var EmConfUtility $emConfUtility */
        $emConfUtility = GeneralUtility::makeInstance(EmConfUtility::class);
        $extensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        $structureBase = [];

        foreach ($extensions as $extension) {
            $extensionConfiguration = $emConfUtility->includeEmConf([
                'key' => $extension,
                'siteRelPath' => ExtensionManagementUtility::siteRelPath($extension)
            ]);
            if ($extensionConfiguration === false) {
                continue;
            }

            if (isset($extensionConfiguration['uploadfolder']) && (bool)$extensionConfiguration['uploadfolder']) {
                $structureBase[] = $this->getExtensionUploadDirectory($extension);
            }

            if (!empty($extensionConfiguration['createDirs'])) {
                foreach (GeneralUtility::trimExplode(',', $extensionConfiguration['createDirs']) as $directoryToCreate) {
                    $directory = GeneralUtility::resolveBackPath(PATH_site . $directoryToCreate);
                    if (StringUtility::beginsWith($directory, PATH_site)) {
                        // Only create directories within TYPO3 root.
                        $structureBase[] = $this->getDirectoryNodeByPath(substr($directory, strlen(PATH_site)));
                    }
                }
            }
        }

        return $structureBase;
    }

    /**
     * Extension uploads directory.
     *
     *
     * @param string $extension Extension key
     * @return array
     */
    protected function getExtensionUploadDirectory($extension)
    {
        return $this->getDirectoryNodeByPath('uploads/tx_' . str_replace('_', '', $extension));
    }

    /**
     * Build directory nodes by given $path
     *
     * @param string $path Path to directory
     * @return array
     */
    protected function getDirectoryNodeByPath($path)
    {
        $baseNode = [];
        $parts = GeneralUtility::trimExplode('/', $path, true);
        $node = &$baseNode;
        foreach ($parts as $part) {
            $node[0] = [
                'name' => $part,
                'type' => DirectoryNode::class,
                'targetPermission' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'],
                'children' => []
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
    protected function appendStructureDefinition(array $original, array $additional)
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
