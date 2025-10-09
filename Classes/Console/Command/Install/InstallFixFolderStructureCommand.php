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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\WebserverType;

class InstallFixFolderStructureCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Fix folder structure');
        $this->setHelp(
            <<<'EOH'
Automatically create files and folders, required for a TYPO3 installation.

This command creates the required folder structure needed for TYPO3 including extensions.
EOH
        );
        $this->addArgument(
            'webServerConfig',
            InputArgument::OPTIONAL,
            'Web server config file to install in document root (`none`, `apache`, `iis`)',
            'none'
        );
    }

    /**
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TYPO3 cli bootstrap initializes a user,
        // which will cause a fatal error when trying to store the flash messages in the user session.
        unset($GLOBALS['BE_USER']);
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $messages = $folderStructureFactory
            ->getStructure(WebserverType::fromType($input->getArgument('webServerConfig')))
            ->fix()
            ->getAllMessagesAndFlush();

        if (empty($messages)) {
            $output->writeln('<info>No action performed!</info>');
        } else {
            $output->writeln('<info>The following directory structure has been fixed:</info>');
            foreach ($messages as $fixedStatusObject) {
                $output->writeln($fixedStatusObject->getTitle());
            }
        }

        return 0;
    }
}
