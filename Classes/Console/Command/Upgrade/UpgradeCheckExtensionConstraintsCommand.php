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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;

class UpgradeCheckExtensionConstraintsCommand extends AbstractConvertedCommand
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
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
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
                TYPO3_version
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
