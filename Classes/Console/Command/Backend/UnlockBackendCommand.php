<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Backend;

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

class UnlockBackendCommand extends Command implements RelatableCommandInterface
{
    public function getRelatedCommandNames(): array
    {
        return [
            'typo3_console:backend:lock',
        ];
    }

    protected function configure()
    {
        $this->setDescription('Unlock backend');
        $this->setHelp('Allow backend access again (e.g. after having been locked with backend:lock command).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = PATH_typo3conf . 'LOCK_BACKEND';
        if (!@is_file($fileName)) {
            $output->writeln('<info>Backend is already unlocked.</info>');

            return 0;
        }
        unlink($fileName);
        if (@is_file($fileName)) {
            $output->writeln('<error>Could not remove lock file \'typo3conf/LOCK_BACKEND\'.</error>');

            return 2;
        }
        $output->writeln('<info>Backend lock is removed. User can now access the backend again.</info>');

        return 0;
    }
}
