<?php

declare(strict_types=1);
namespace Helhum\Typo3Console\Command\InstallTool;

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

use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Install\Service\EnableFileService;

class UnlockInstallToolCommand extends Command implements RelatableCommandInterface
{
    public function getRelatedCommandNames(): array
    {
        return [
            'typo3_console:install:lock',
        ];
    }

    protected function configure()
    {
        $this->setDescription('Unlock Install Tool');
        $this->setHelp('Allow install tool access.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (EnableFileService::checkInstallToolEnableFile()) {
            $output->writeln('<info>Install Tool is already unlocked.</info>');

            return 0;
        }

        if (!EnableFileService::createInstallToolEnableFile()) {
            $output->writeln('<error>Could not create file \'typo3conf/ENABLE_INSTALL_TOOL\'.</error>');

            return 1;
        }

        $output->writeln(
            '<info>Install Tool has been unlocked and can be accessed now at \'typo3/install.php\'.</info>'
        );

        return 0;
    }
}
