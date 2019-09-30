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

class InstallDatabaseDataCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adminUserName = $input->getOption('admin-user-name');
        $adminPassword = $input->getOption('admin-password');
        $siteName = $input->getOption('site-name');

        $this->executeActionWithArguments('DatabaseData', [
            'username' => $adminUserName,
            'password' => $adminPassword,
            'sitename' => $siteName,
        ]);
    }
}
