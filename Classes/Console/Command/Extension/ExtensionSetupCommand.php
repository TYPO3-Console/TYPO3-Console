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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class ExtensionSetupCommand extends Command
{
    use SetupExtensionsTrait;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        string $name = null,
        Dispatcher $signalSlotDispatcher = null,
        PackageManager $packageManager = null
    ) {
        parent::__construct($name);

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->signalSlotDispatcher = $signalSlotDispatcher ?? $this->objectManager->get(Dispatcher::class);
        $this->packageManager = $packageManager ?? $this->objectManager->get(PackageManager::class);
    }

    protected function configure()
    {
        $this->setDescription('Set up extension(s)');
        $this->setHelp(
            <<<'EOH'
Sets up one or more extensions by key.
Set up means:

- Database migrations and additions
- Importing files and data
- Writing default extension configuration
EOH
        );
        $this->addArgument(
            'extensionKeys',
            InputArgument::REQUIRED,
            'Extension keys to set up. Separate multiple extension keys with comma'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $extensionKeys = $input->getArgument('extensionKeys');
        $verbose = $output->isVerbose();

        $packages = [];

        foreach ($extensionKeys as $extensionKey) {
            $packages[] = $this->packageManager->getPackage($extensionKey);
        }

        $this->setupExtensions($packages, $verbose);
    }
}
