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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class ExtensionListCommand extends Command
{
    use EmitPackagesMayHaveChangedSignalTrait;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    public function __construct(
        string $name = null,
        Dispatcher $signalSlotDispatcher = null,
        PackageManager $packageManager = null
    ) {
        parent::__construct($name);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->signalSlotDispatcher = $signalSlotDispatcher ?? $objectManager->get(Dispatcher::class);
        $this->packageManager = $packageManager ?? $objectManager->get(PackageManager::class);
    }

    protected function configure()
    {
        $this->setDescription('List extensions that are available in the system');
        $this->addOption(
            'active',
            'a',
            InputOption::VALUE_NONE,
            'Only show active extensions'
        );
        $this->addOption(
            'inactive',
            'i',
            InputOption::VALUE_NONE,
            'Only show inactive extensions'
        );
        $this->addOption(
            'raw',
            'r',
            InputOption::VALUE_NONE,
            'Enable machine readable output (just extension keys separated by line feed)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $active = $input->getOption('active');
        $inactive = $input->getOption('inactive');
        $raw = $input->getOption('raw');

        $extensionInformation = [];

        if (!$active || $inactive) {
            $this->emitPackagesMayHaveChangedSignal();
            $packages = $this->packageManager->getAvailablePackages();
        } else {
            $packages = $this->packageManager->getActivePackages();
        }

        foreach ($packages as $package) {
            if ($inactive && $this->packageManager->isPackageActive($package->getPackageKey())) {
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
