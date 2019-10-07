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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Install\InstallStepActionExecutor;
use Helhum\Typo3Console\Install\Upgrade\SilentConfigurationUpgrade;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallDatabaseConnectCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setHidden(true);
        $this->setDescription('Connect to database');
        $this->setHelp('Database connection details');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputOption(
                'database-user-name',
                null,
                InputOption::VALUE_REQUIRED,
                'User name for database server',
                ''
            ),
            new InputOption(
                'database-user-password',
                null,
                InputOption::VALUE_REQUIRED,
                'User password for database server',
                ''
            ),
            new InputOption(
                'database-host-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Host name of database server',
                '127.0.0.1'
            ),
            new InputOption(
                'database-port',
                null,
                InputOption::VALUE_REQUIRED,
                'TCP Port of database server',
                '3306'
            ),
            new InputOption(
                'database-socket',
                null,
                InputOption::VALUE_REQUIRED,
                'Unix Socket to connect to',
                ''
            ),
            new InputOption(
                'database-driver',
                null,
                InputOption::VALUE_REQUIRED,
                'Database connection type',
                'mysqli'
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
        $databaseUserName = $input->getOption('database-user-name');
        $databaseUserPassword = $input->getOption('database-user-password');
        $databaseHostName = $input->getOption('database-host-name');
        $databasePort = $input->getOption('database-port');
        $databaseSocket = $input->getOption('database-socket');
        $databaseDriver = $input->getOption('database-driver');

        $installStepActionExecutor = new InstallStepActionExecutor(
            new SilentConfigurationUpgrade()
        );
        $output->write(
            serialize(
                $installStepActionExecutor->executeActionWithArguments(
                    'databaseConnect',
                    [
                        'host' => $databaseHostName,
                        'port' => $databasePort,
                        'username' => $databaseUserName,
                        'password' => $databaseUserPassword,
                        'socket' => $databaseSocket,
                        'driver' => $databaseDriver,
                    ]
                )
            ),
            false,
            OutputInterface::OUTPUT_RAW
        );
    }
}
