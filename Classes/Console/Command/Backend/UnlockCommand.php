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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UnlockCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Unlock backend');
        $this->setHelp('Allow backend access again (e.g. after having been locked with backend:lock command).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $fileName = PATH_typo3conf . 'LOCK_BACKEND';
        if (!@is_file($fileName)) {
            $io->warning('Backend is already unlocked');

            return 0;
        }
        unlink($fileName);
        if (@is_file($fileName)) {
            $io->error('Could not remove lock file \'typo3conf/LOCK_BACKEND\'.');

            return 2;
        }
        $io->success('Backend lock is removed. User can now access the backend again.');
    }
}
