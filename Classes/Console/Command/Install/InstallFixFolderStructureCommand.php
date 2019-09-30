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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class InstallFixFolderStructureCommand extends Command
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    public function __construct(string $name = null, PackageManager $packageManager = null)
    {
        parent::__construct($name);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->packageManager = $packageManager ?? $objectManager->get(PackageManager::class);
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



Related commands
~~~~~~~~~~~~~~~~

`install:generatepackagestates`
  Generate PackageStates.php file
EOH
        );
    }

    /**
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException
     * @throws \TYPO3\CMS\Install\Status\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folderStructureFactory = new ExtensionFactory($this->packageManager);
        $fixedStatusObjects = $folderStructureFactory
            ->getStructure()
            ->fix();

        if (empty($fixedStatusObjects)) {
            $output->writeln('<info>No action performed!</info>');
        } else {
            $this->outputLine('<info>The following directory structure has been fixed:</info>');
            foreach ($fixedStatusObjects as $fixedStatusObject) {
                $output->writeln($fixedStatusObject->getTitle());
            }
        }
    }
}
