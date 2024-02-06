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

class InstallDefaultConfigurationCommand extends Command
{
    public function __construct(private readonly BootService $bootService)
    {
        parent::__construct('install:defaultconfiguration');
    }

    protected function configure(): void
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
        $this->addOption(
            'site-base-url',
            null,
            InputOption::VALUE_REQUIRED,
            'When `site-setup-type` is set to `site`, this base url is used for the created site configuration',
            '/'
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

        $siteSetupType = $input->getOption('site-setup-type');
        $arguments['siteUrl'] = $input->getOption('site-base-url');
        switch ($siteSetupType) {
            case 'site':
                $arguments['sitesetup'] = 'createsite';
                break;
            case 'no':
            default:
                $arguments['sitesetup'] = 'none';
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

        return 0;
    }
}
