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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is where the hard work happens in a fully bootstrapped TYPO3
 * It will be called as sub process
 *
 * @internal
 */
class UpgradeSubProcessCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setHidden(true);
        $this->setDescription('This is where the hard work happens in a fully bootstrapped TYPO3');
        $this->setHelp('It will be called as sub process');
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
                'upgradeCommand',
                InputArgument::REQUIRED
            ),
            new InputArgument(
                'arguments',
                InputArgument::REQUIRED,
                'Serialized arguments'
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
        $upgradeCommand = $input->getArgument('upgradeCommand');
        $arguments = $input->getArgument('arguments');

        $arguments = unserialize($arguments, ['allowed_classes' => false]);
        $result = (new UpgradeHandling())->$upgradeCommand(...$arguments);
        $output->write(serialize($result), false, OutputInterface::OUTPUT_RAW);
    }
}
