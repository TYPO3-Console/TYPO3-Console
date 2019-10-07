<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Extension;

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionListCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('List extensions that are available in the system');
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
                'active',
                'a',
                InputOption::VALUE_NONE,
                'Only show active extensions'
            ),
            new InputOption(
                'inactive',
                'i',
                InputOption::VALUE_NONE,
                'Only show inactive extensions'
            ),
            new InputOption(
                'raw',
                'r',
                InputOption::VALUE_NONE,
                'Enable machine readable output (just extension keys separated by line feed)'
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
        $io = new SymfonyStyle($input, $output);

        $active = $input->getOption('active');
        $inactive = $input->getOption('inactive');
        $raw = $input->getOption('raw');

        $extensionInformation = [];
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        if (!$active || $inactive) {
            $packageManager->scanAvailablePackages();
            $packages = $packageManager->getAvailablePackages();
        } else {
            $packages = $packageManager->getActivePackages();
        }

        foreach ($packages as $package) {
            if ($inactive && $packageManager->isPackageActive($package->getPackageKey())) {
                continue;
            }
            $metaData = $package->getPackageMetaData();
            $extensionInformation[] = [
                'package_key' => $package->getPackageKey(),
                'version' => $metaData->getVersion(),
                'description' => $metaData->getDescription(),
            ];
        }

        if ($raw) {
            $io->writeln(sprintf('%s', implode(PHP_EOL, array_column($extensionInformation, 'package_key'))));
        } else {
            $io->table(
                ['Extension key', 'Version', 'Description'],
                $extensionInformation
            );
        }
    }
}
