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

class UpgradeCheckExtensionCompatibilityCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Checks for broken extensions');
        $this->setHelp(
            <<<'EOH'
This command in meant to be executed as sub process as it is is subject to cause fatal errors
when extensions have broken (incompatible) code
EOH
        );
        $this->setDefinition(
            [
                new InputArgument(
                    'extensionKey',
                    InputArgument::REQUIRED,
                    'Extension key for extension to check'
                ),
                new InputOption(
                    'config-only',
                    'c',
                    InputOption::VALUE_NONE,
                    ''
                ),
            ]
        );
    }

    public function isHidden()
    {
        return true;
    }

    public function isEnabled()
    {
        return getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') === false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensionKey = $input->getArgument('extensionKey');
        $configOnly = $input->getOption('config-only');

        $output->writeln(\json_encode((new UpgradeHandling())->isCompatible($extensionKey, $configOnly)));

        return 0;
    }
}
