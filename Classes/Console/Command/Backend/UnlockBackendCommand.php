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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockBackendCommand extends AbstractConvertedCommand implements RelatableCommandInterface
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
