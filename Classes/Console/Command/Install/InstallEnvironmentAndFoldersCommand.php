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
use Symfony\Component\Console\Output\OutputInterface;

class InstallEnvironmentAndFoldersCommand extends Command
{
    protected function configure()
    {
        $this->setHidden(true);
        $this->setDescription('Check environment / create folders');
        $this->setHelp(
            <<<'EOH'
Check environment and create folder structure
EOH
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installStepActionExecutor = new InstallStepActionExecutor(
            new SilentConfigurationUpgrade()
        );
        $output->write(
            serialize(
                $installStepActionExecutor->executeActionWithArguments('environmentAndFolders')
            ),
            false,
            OutputInterface::OUTPUT_RAW
        );
    }
}
