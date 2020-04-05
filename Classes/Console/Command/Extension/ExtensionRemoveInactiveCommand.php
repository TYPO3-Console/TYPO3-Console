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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class ExtensionRemoveInactiveCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Removes all extensions that are not marked as active');
        $this->setHelp(
            <<<'EOH'
Directories of inactive extension are <comment>removed</comment> from <code>typo3/sysext</code> and <code>typo3conf/ext</code>.
This is a one way command with no way back. Don't blame anybody if this command destroys your data.
<comment>Handle with care!</comment>

<warning>This command is deprecated.</warning>

  Instead of adding extensions and then removing them, just don't add them in the first place.
EOH
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'The option has to be specified, otherwise nothing happens'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @deprecated in 5.0 will be removed with 6.0
        $force = $input->getOption('force');

        $output->writeln('<warning>This command is deprecated and will be removed with TYPO3 Console 6.0</warning>');
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        if ($force) {
            $activePackages = $packageManager->getActivePackages();
            $packageManager->scanAvailablePackages();
            foreach ($packageManager->getAvailablePackages() as $package) {
                if (empty($activePackages[$package->getPackageKey()])) {
                    $packageManager->unregisterPackage($package);
                    if (is_dir($package->getPackagePath())) {
                        GeneralUtility::flushDirectory($package->getPackagePath());
                        $removedPaths[] = PathUtility::stripPathSitePrefix($package->getPackagePath());
                    }
                }
            }
            $packageManager->forceSortAndSavePackageStates();
            if (!empty($removedPaths)) {
                $output->writeln(
                    '<info>The following directories have been removed:</info>' . chr(10) . implode(chr(10), $removedPaths)
                );
            } else {
                $output->writeln('<info>Nothing was removed</info>');
            }
        } else {
            $output->writeln('<warning>Operation not confirmed and has been skipped</warning>');

            return 1;
        }

        return 0;
    }

    public function isHidden(): bool
    {
        $application = $this->getApplication();
        if (!$application instanceof Application || getenv('TYPO3_CONSOLE_RENDERING_REFERENCE')) {
            return true;
        }

        return !$application->isComposerManaged();
    }
}
