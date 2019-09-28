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
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class ExtensionSetupActiveCommand extends Command
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
        PackageManager $packageManager = null
    ) {
        parent::__construct($name);

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->signalSlotDispatcher = $signalSlotDispatcher ?? $this->objectManager->get(Dispatcher::class);
        $this->packageManager = $packageManager ?? $this->objectManager->get(PackageManager::class);
    }

    protected function configure()
    {
        $this->setDescription('Set up all active extensions');
        $this->setHelp(
            <<<'EOH'
Sets up all extensions that are marked as active in the system.

This command is especially useful for deployment, where extensions
are already marked as active, but have not been set up yet or might have changed. It ensures every necessary
setup step for the (changed) extensions is performed.
As an additional benefit no caches are flushed, which significantly improves performance of this command
and avoids unnecessary cache clearing.



Related commands
~~~~~~~~~~~~~~~~

`extension:setup`
  Set up extension(s)
`install:generatepackagestates`
  Generate PackageStates.php file
`cache:flush`
  Flush all caches
EOH
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $verbose = $output->isVerbose();
        $this->setupExtensions($this->packageManager->getActivePackages(), $verbose);
    }
}
