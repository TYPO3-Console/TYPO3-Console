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

class InstallDefaultConfigurationCommand extends Command
{
    protected function configure()
    {
        $this->setHidden(true);
        $this->setDescription('Write default configuration');
        $this->setHelp(
            <<<'EOH'
Writes default configuration for the TYPO3 site based on the
provided $siteSetupType. Valid values are:

- site (which creates an empty root page and setup)
- no (which unsurprisingly does nothing at all)

In non composer mode the following option is also available:
- dist (which loads a list of distributions you can install)
EOH
        );
        $this->addOption(
            'site-setup-type',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify the setup type: Create empty root page (site), Do nothing (no)',
            'no'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteSetupType = $input->getOption('site-setup-type');

        switch ($siteSetupType) {
            case 'site':
            case 'createsite':
                $arguments = ['sitesetup' => 'createsite'];
                break;
            case 'no':
            default:
                $arguments = ['sitesetup' => 'none'];
        }

        $installStepActionExecutor = new InstallStepActionExecutor(
            new SilentConfigurationUpgrade()
        );
        $output->write(
            serialize(
                $installStepActionExecutor->executeActionWithArguments(
                    'defaultConfiguration',
                    $arguments
                )
            ),
            false,
            OutputInterface::OUTPUT_RAW
        );
    }
}
