<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Upgrade;

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

use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;

class UpgradeCheckExtensionConstraintsCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Check TYPO3 version constraints of extensions');
        $this->setHelp(
            <<<'EOH'
This command is especially useful **before** switching sources to a new TYPO3 version.
It checks the version constraints of all third party extensions against a given TYPO3 version.
It therefore relies on the constraints to be correct.
EOH
        );
        $this->setDefinition([
            new InputArgument(
                'extensionKeys',
                InputArgument::OPTIONAL,
                'Extension keys to check. Separate multiple extension keys with comma'
            ),
            new InputOption(
                'typo3-version',
                null,
                InputOption::VALUE_REQUIRED,
                'TYPO3 version to check against. Defaults to current TYPO3 version',
                (new Typo3Version())->getVersion()
            ),
        ]);
    }

    public function isHidden()
    {
        return !getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') && Environment::isComposerMode();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (Environment::isComposerMode()) {
            $output->writeln('<error>The command "upgrade:checkextensionconstraints" is not available in Composer mode, because Composer already enforces such constraints.</error>');

            return 1;
        }

        $extensionKeys = $input->getArgument('extensionKeys');
        $typo3Version = $input->getOption('typo3-version');
        $upgradeHandling = new UpgradeHandling();
        if ($extensionKeys === null) {
            $failedPackageMessages = $upgradeHandling->matchAllExtensionConstraints($typo3Version);
        } else {
            $failedPackageMessages = [];
            foreach (explode(',', $extensionKeys) as $extensionKey) {
                try {
                    if (!empty($result = $upgradeHandling->matchExtensionConstraints($extensionKey, $typo3Version))) {
                        $failedPackageMessages[$extensionKey] = $result;
                    }
                } catch (UnknownPackageException $e) {
                    $output->writeln(sprintf(
                        '<warning>Extension "%s" is not found in the system</warning>',
                        $extensionKey
                    ));
                }
            }
        }
        foreach ($failedPackageMessages as $constraintMessage) {
            $output->writeln(sprintf('<error>%s</error>', $constraintMessage));
        }
        if (empty($failedPackageMessages)) {
            $output->writeln(sprintf(
                '<info>All third party extensions claim to be compatible with TYPO3 version %s</info>',
                $typo3Version
            ));

            return 0;
        }

        return 1;
    }
}
