<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Install;

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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstallFixFolderStructureCommand extends AbstractConvertedCommand implements RelatableCommandInterface
{
    public function getRelatedCommandNames(): array
    {
        return [
            'typo3_console:install:generatepackagestates',
        ];
    }

    protected function configure()
    {
        $this->setDescription('Fix folder structure');
        $this->setHelp(
            <<<'EOH'
Automatically create files and folders, required for a TYPO3 installation.

This command creates the required folder structure needed for TYPO3 including extensions.
It is recommended to be executed <b>after</b> executing
<code>typo3cms install:generatepackagestates</code>, to ensure proper generation of
required folders for all active extensions.
EOH
        );
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    /**
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException
     * @throws \TYPO3\CMS\Install\Status\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folderStructureFactory = new ExtensionFactory(GeneralUtility::makeInstance(PackageManager::class));
        $fixedStatusObjects = $folderStructureFactory
            ->getStructure()
            ->fix();

        if (empty($fixedStatusObjects)) {
            $output->writeln('<info>No action performed!</info>');
        } else {
            $output->writeln('<info>The following directory structure has been fixed:</info>');
            foreach ($fixedStatusObjects as $fixedStatusObject) {
                $output->writeln($fixedStatusObject->getTitle());
            }
        }
    }
}
