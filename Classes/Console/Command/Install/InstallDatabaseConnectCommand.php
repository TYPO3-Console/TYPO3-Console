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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class InstallDatabaseConnectCommand extends Command
{
    use ExecuteActionWithArgumentsTrait;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    public function __construct(
        string $name = null,
        PackageManager $packageManager = null
    ) {
        parent::__construct($name);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->packageManager = $packageManager ?? $objectManager->get(PackageManager::class);
    }

    protected function configure()
    {
        $this->setDescription('Connect to database');
        $this->setHelp('Database connection details');
        $this->addOption(
            'database-user-name',
            null,
            InputOption::VALUE_REQUIRED,
            'User name for database server',
            ''
        );
        $this->addOption(
            'database-user-password',
            null,
            InputOption::VALUE_REQUIRED,
            'User password for database server',
            ''
        );
        $this->addOption(
            'database-host-name',
            null,
            InputOption::VALUE_REQUIRED,
            'Host name of database server',
            '127.0.0.1'
        );
        $this->addOption(
            'database-port',
            null,
            InputOption::VALUE_REQUIRED,
            'TCP Port of database server',
            '3306'
        );
        $this->addOption(
            'database-socket',
            null,
            InputOption::VALUE_REQUIRED,
            'Unix Socket to connect to',
            ''
        );
        $this->addOption(
            'database-driver',
            null,
            InputOption::VALUE_REQUIRED,
            'Database connection type',
            'mysqli'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $databaseUserName = $input->getOption('database-user-name');
        $databaseUserPassword = $input->getOption('database-user-password');
        $databaseHostName = $input->getOption('database-host-name');
        $databasePort = $input->getOption('database-port');
        $databaseSocket = $input->getOption('database-socket');
        $databaseDriver = $input->getOption('database-driver');

        $this->executeActionWithArguments('databaseConnect', [
            'host' => $databaseHostName,
            'port' => $databasePort,
            'username' => $databaseUserName,
            'password' => $databaseUserPassword,
            'socket' => $databaseSocket,
            'driver' => $databaseDriver,
        ]);
    }
}
