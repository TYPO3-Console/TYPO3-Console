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
use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallExtensionSetupIfPossibleCommand extends AbstractConvertedCommand implements RelatableCommandInterface
{
    public function getRelatedCommandNames(): array
    {
        return [
            'typo3_console:extension:setupactive',
        ];
    }

    protected function configure()
    {
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
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [];
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
        $commandDispatcher = CommandDispatcher::createFromCommandRun();
        try {
            $output->writeln($commandDispatcher->executeCommand('database:updateschema'));
            $output->writeln($commandDispatcher->executeCommand('cache:flush'));
            $output->writeln($commandDispatcher->executeCommand('extension:setupactive'));
        } catch (FailedSubProcessCommandException $e) {
            $output->writeln('<warning>Extension setup skipped.</warning>');
        }
    }
}
