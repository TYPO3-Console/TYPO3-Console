<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Install;

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

use Helhum\Typo3Console\Install\InstallStepActionExecutor;
use Helhum\Typo3Console\Install\Upgrade\SilentConfigurationUpgrade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallDatabaseSelectCommand extends Command
{
    protected function configure()
    {
        $this->setHidden(true);
        $this->setDescription('Select database');
        $this->setHelp('Select a database by name');
        $this->addOption(
            'database-name',
            null,
            InputOption::VALUE_REQUIRED,
            'Name of the database'
        );
        $this->addOption(
            'use-existing-database',
            null,
            InputOption::VALUE_NONE,
            'Use already existing database?'
        );
    }

    public function isEnabled()
    {
        return getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') === false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $databaseName = $input->getOption('database-name');
        $useExistingDatabase = $input->getOption('use-existing-database');
        $selectType = $useExistingDatabase ? 'existing' : 'new';

        $installStepActionExecutor = new InstallStepActionExecutor(
            new SilentConfigurationUpgrade()
        );
        $output->write(
            serialize(
                $installStepActionExecutor->executeActionWithArguments(
                    'databaseSelect',
                    [
                        'type' => $selectType,
                        $selectType => $databaseName,
                    ]
                )
            ),
            false,
            OutputInterface::OUTPUT_RAW
        );

        return 0;
    }
}
