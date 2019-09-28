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

class ExtensionDeactivateCommand extends Command
{
    use SetupExtensionsTrait, ShowDeprecationMessageTrait;

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
        $this->setDescription('Deactivate extension(s)');
        $this->setHelp(
            <<<'EOH'
Deactivates one or more extensions by key.
Marks extensions as inactive in the system and clears caches for every deactivated extension.

This command is deprecated (and hidden) in Composer mode.
EOH
        );
        $this->addArgument(
            'extensionKeys',
            InputArgument::REQUIRED,
            'Extension keys to deactivate. Separate multiple extension keys with comma'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @deprecated for composer usage in 5.0 will be removed with 6.0
        $this->output = $output;

        $extensionKeys = explode(',', $input->getArgument('extensionKeys'));

        $this->showDeprecationMessageIfApplicable();
        foreach ($extensionKeys as $extensionKey) {
            $this->getExtensionInstaller()->uninstall($extensionKey);
        }
        $extensionKeysAsString = implode('", "', $extensionKeys);
        if (count($extensionKeys) === 1) {
            $output->writeln(sprintf('<info>Extension "%s" is now inactive.</info>', $extensionKeysAsString));
        } else {
            $output->writeln(sprintf('<info>Extensions "%s" are now inactive.</info>', $extensionKeysAsString));
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
