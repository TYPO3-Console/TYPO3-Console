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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LockBackendCommand extends AbstractConvertedCommand implements RelatableCommandInterface
{
    public function getRelatedCommandNames(): array
    {
        return [
            'typo3_console:backend:unlock',
        ];
    }

    protected function configure()
    {
        $this->setDescription('Lock backend');
        $this->setHelp('Deny backend access for <b>every</b> user (including admins).');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputOption(
                'redirect-url',
                null,
                InputOption::VALUE_REQUIRED,
                'URL to redirect to when the backend is accessed'
            ),
        ];
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
        $redirectUrl = $input->getOption('redirect-url');
        $fileName = PATH_typo3conf . 'LOCK_BACKEND';
        if (@is_file($fileName)) {
            $output->writeln('<info>Backend is already locked.</info>');

            return 0;
        }
        GeneralUtility::writeFile($fileName, (string)$redirectUrl);
        if (!@is_file($fileName)) {
            $output->writeln('<error>Could not create lock file \'typo3conf/LOCK_BACKEND\'.</error>');

            return 2;
        }
        $output->writeln('<info>Backend has been locked. Access is denied for every user until it is unlocked again.</info>');
        if ($redirectUrl !== null) {
            $output->writeln('Any access to the backend will be redirected to: \'' . $redirectUrl . '\'');
        }

        return 0;
    }
}
