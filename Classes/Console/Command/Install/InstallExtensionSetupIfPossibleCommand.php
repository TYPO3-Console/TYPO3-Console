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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallExtensionSetupIfPossibleCommand extends Command
{
    protected function configure()
    {
        $this->addOption(
            'fail-on-error',
            null,
            InputOption::VALUE_NONE,
            'Instead of gracefully exiting this command if something goes wrong, throw an error'
        );
        $this->setDescription('Setup TYPO3 with extensions if possible');
        $this->setHelp(
            <<<'EOH'
This command tries up all TYPO3 extensions, but quits gracefully if this is not possible.
This can be used in <code>composer.json</code> scripts to ensure that extensions
are always set up correctly after a composer run on development systems,
but does not fail on packaging for deployment where no database connection is available.

Besides that, it can be used for a first deploy of a TYPO3 instance in a new environment,
but also works for subsequent deployments.
EOH
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandDispatcher = CommandDispatcher::createFromCommandRun();
        try {
            $output->writeln($commandDispatcher->executeCommand('database:updateschema'));
            $output->writeln($commandDispatcher->executeCommand('cache:flush', ['--group', 'system']));
            $output->writeln($commandDispatcher->executeCommand('extension:setup'));
        } catch (FailedSubProcessCommandException $e) {
            if ($input->getOption('fail-on-error')) {
                throw $e;
            }
            $output->writeln('<warning>Extension setup skipped.</warning>');
        }

        return 0;
    }
}
