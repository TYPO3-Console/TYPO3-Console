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

use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class ExtensionActivateCommand extends Command
{
    use EmitPackagesMayHaveChangedSignalTrait, SetupExtensionsTrait, ShowDeprecationMessageTrait;

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
        $this->setDescription('Activate extension(s)');
        $this->setHelp(
            <<<'EOH'
Activates one or more extensions by key.
Marks extensions as active, sets them up and clears caches for every activated extension.

This command is deprecated (and hidden) in Composer mode.
EOH
        );
        $this->addArgument(
            'extensionKeys',
            InputArgument::REQUIRED,
            'Extension keys to activate. Separate multiple extension keys with comma'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @deprecated for composer usage in 5.0 will be removed with 6.0
        $this->output = $output;

        $extensionKeys = explode(',', $input->getArgument('extensionKeys'));
        $verbose = $output->isVerbose();

        $this->showDeprecationMessageIfApplicable();
        $this->emitPackagesMayHaveChangedSignal();
        $activatedExtensions = [];
        $extensionsToSetUp = [];
        foreach ($extensionKeys as $extensionKey) {
            $extensionsToSetUp[] = $this->packageManager->getPackage($extensionKey);
            if (!$this->packageManager->isPackageActive($extensionKey)) {
                $this->packageManager->activatePackage($extensionKey);
                $activatedExtensions[] = $extensionKey;
            }
        }

        if (!empty($activatedExtensions)) {
            $cacheService = new CacheService();
            $cacheService->flush();

            $this->getExtensionInstaller()->reloadCaches();

            $extensionKeysAsString = implode('", "', $activatedExtensions);
            if (count($activatedExtensions) === 1) {
                $output->writeln(sprintf('<info>Extension "%s" is now active.</info>', $extensionKeysAsString));
            } else {
                $output->writeln(sprintf('<info>Extensions "%s" are now active.</info>', $extensionKeysAsString));
            }
        }

        if (!empty($extensionsToSetUp)) {
            $this->setupExtensions($extensionsToSetUp, $verbose);
        }
    }

    public function isEnabled(): bool
    {
        $application = $this->getApplication();
        if (!$application instanceof Application || getenv('TYPO3_CONSOLE_RENDERING_REFERENCE')) {
            return true;
        }

        return !$application->isComposerManaged();
    }
}
