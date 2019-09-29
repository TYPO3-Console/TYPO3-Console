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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is where the hard work happens in a fully bootstrapped TYPO3
 * It will be called as sub process
 *
 * @internal
 */
class UpgradeSubProcessCommand extends Command
{
    const ARG_UPGRADE_COMMAND = 'upgradeCommand';
    const ARG_ARGUMENTS = 'arguments';

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
        $this->setDescription('This is where the hard work happens in a fully bootstrapped TYPO3');
        $this->setHelp('It will be called as sub process');
        $this->addArgument(
            self::ARG_UPGRADE_COMMAND,
            InputArgument::REQUIRED
        );
        $this->addArgument(
            self::ARG_ARGUMENTS,
            InputArgument::REQUIRED,
            'Serialized arguments'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $upgradeCommand = $input->getArgument(self::ARG_UPGRADE_COMMAND);
        $arguments = $input->getArgument(self::ARG_ARGUMENTS);

        $arguments = unserialize($arguments, ['allowed_classes' => false]);
        $result = $this->upgradeHandling->$upgradeCommand(...$arguments);
        $output->writeln(serialize($result));
    }
}
