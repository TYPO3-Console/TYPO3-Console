<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Extension;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;

class DumpAutoloadCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Dump class autoload information for extensions');
        $this->setHelp(
            <<<'EOH'
Updates class loading information in non Composer managed TYPO3 installations.

This command is only needed during development. The extension manager takes care
creating or updating this info properly during extension (de-)activation.

This command is not available in Composer mode.
EOH
        );
    }

    public function isEnabled(): bool
    {
        $application = $this->getApplication();
        if (!$application instanceof Application || getenv('TYPO3_CONSOLE_RENDERING_REFERENCE')) {
            return true;
        }

        return !$application->isComposerManaged();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ClassLoadingInformation::dumpClassLoadingInformation();
        $output->writeln('<info>Class Loading information has been updated.</info>');
    }
}
