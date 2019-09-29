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
    const ARG_EXTENSION_KEY = 'extensionKeys';
    const OPT_CONFIG_ONLY = 'configOnly';

    /**
     * @var UpgradeHandling
     */
    private $upgradeHandling;

    /**
     * @param UpgradeHandling|null $upgradeHandling
     */
    public function __construct(
        string $name = null,
        UpgradeHandling $upgradeHandling = null
    ) {
        parent::__construct($name);

        $this->upgradeHandling = $upgradeHandling ?? new UpgradeHandling();
    }

    protected function configure()
    {
        $this->setDescription('Checks for broken extensions');
        $this->setHelp(
            <<<'EOH'
This command in meant to be executed as sub process as it is is subject to cause fatal errors
when extensions have broken (incompatible) code
EOH
        );
        $this->addArgument(
            self::ARG_EXTENSION_KEY,
            InputArgument::REQUIRED,
            'Extension key for extension to check'
        );
        $this->addOption(
            self::OPT_CONFIG_ONLY,
            'c',
            InputOption::VALUE_NONE,
            ''
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensionKey = $input->getArgument(self::ARG_EXTENSION_KEY);
        $configOnly = $input->getOption(self::OPT_CONFIG_ONLY);

        $output->writeln(\json_encode($this->upgradeHandling->isCompatible($extensionKey, $configOnly)));
    }
}
