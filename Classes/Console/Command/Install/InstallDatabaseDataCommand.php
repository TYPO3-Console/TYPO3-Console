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
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Information\Typo3Version;

class InstallDatabaseDataCommand extends Command
{
    public function __construct(private readonly BootService $bootService)
    {
        parent::__construct('install:databasedata');
    }

    protected function configure(): void
    {
        $this->setHidden(true);
        $this->setDescription('Add database data');
        $this->setHelp('Adds admin user and site name in database');
        $this->addOption(
            'admin-user-name',
            null,
            InputOption::VALUE_REQUIRED,
            'Username of to be created administrative user account'
        );
        $this->addOption(
            'admin-password',
            null,
            InputOption::VALUE_REQUIRED,
            'Password of to be created administrative user account'
        );
        $this->addOption(
            'site-name',
            null,
            InputOption::VALUE_REQUIRED,
            'Site name',
            'New TYPO3 Console site'
        );
    }

    public function isEnabled(): bool
    {
        return getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') === false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @deprecated with TYPO3 12, this version check can be removed
        $this->bootService->loadExtLocalconfDatabaseAndExtTables(allowCaching: (new Typo3Version())->getMajorVersion() > 11);

        $adminUserName = $input->getOption('admin-user-name');
        $adminPassword = $input->getOption('admin-password');
        $siteName = $input->getOption('site-name');

        $installStepActionExecutor = new InstallStepActionExecutor(
            new SilentConfigurationUpgrade()
        );
        $output->write(
            serialize(
                $installStepActionExecutor->executeActionWithArguments(
                    'databaseData',
                    [
                        'username' => $adminUserName,
                        'password' => $adminPassword,
                        'sitename' => $siteName,
                    ]
                )
            ),
            false,
            OutputInterface::OUTPUT_RAW
        );

        return 0;
    }
}
