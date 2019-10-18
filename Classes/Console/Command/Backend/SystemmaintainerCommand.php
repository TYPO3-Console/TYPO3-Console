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
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemmaintainerCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Add or remove system maintainers');
        $this->setHelp('Add or remove backend user UIDs to the list of system maintainers.');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }
    #
    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputArgument(
                'action',
                InputArgument::REQUIRED,
                'add or remove'
            ),
            new InputArgument(
                'beuserUids',
                InputArgument::REQUIRED,
                'Comma seperated list of backend user UIDs'
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
        $configurationService = new ConfigurationService();
        if (!$configurationService->localIsActive('SYS/systemMaintainers')) {
            $output->writeln('<error>The configuration value SYS/systemMaintainers is not modifiable. Is it forced to a value in Additional Configuration?</error>');

            return 2;
        }

        $action = $input->getArgument('action');
        $beuserUids = $input->getArgument('beuserUids');

        $beuserUidArray = explode(',', $beuserUids);
        foreach ($beuserUidArray as $index => $value) {
            $beuserUidArray[$index] = (int)$value;
        }

        $systemMaintainers = $configurationService->getLocal('SYS/systemMaintainers');

        // TODO Check if backend user exists (not hidden or deleted) and are administrators
        switch ($action) {
            case 'add':
                $systemMaintainers = \array_merge($beuserUidArray, $systemMaintainers);
                $systemMaintainers = \array_map("unserialize", array_unique(array_map("serialize", $systemMaintainers)));
                sort($systemMaintainers, SORT_NUMERIC);
                $configurationService->setLocal('SYS/systemMaintainers', $systemMaintainers);
                $output->writeln(sprintf('<info>Following backend user with UIDs are system maintainers: "%s"</info>', implode(',', $systemMaintainers)));
                break;

            case 'remove':
                $systemMaintainers = \array_diff($systemMaintainers, $beuserUidArray);
                $configurationService->setLocal('SYS/systemMaintainers', array_values($systemMaintainers));
                $output->writeln(sprintf('<info>Following backend user with UIDs are system maintainers: "%s"</info>', implode(',', $systemMaintainers)));
                break;

            default:
                $output->writeln('<warning>Define if you want "add" or "remove" system maintainers.</warning>');
                break;
        }

        return 0;
    }
}
