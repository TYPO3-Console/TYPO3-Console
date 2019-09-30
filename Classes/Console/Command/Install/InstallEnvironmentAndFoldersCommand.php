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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class InstallEnvironmentAndFoldersCommand extends Command
{
    use ExecuteActionWithArgumentsTrait;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var InstallStepActionExecutor
     */
    protected $installStepActionExecutor;

    public function __construct(
        string $name = null,
        PackageManager $packageManager = null,
        InstallStepActionExecutor $installStepActionExecutor = null
    ) {
        parent::__construct($name);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->packageManager = $packageManager ?? $objectManager->get(PackageManager::class);
        $this->installStepActionExecutor = $installStepActionExecutor
            ?? $objectManager->get(InstallStepActionExecutor::class);
    }

    protected function configure()
    {
        $this->setDescription('Check environment / create folders');
        $this->setHelp(
            <<<'EOH'
Check environment and create folder structure
EOH
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeActionWithArguments('environmentAndFolders');
    }
}
