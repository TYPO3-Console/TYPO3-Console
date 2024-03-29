<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Configuration;

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

use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Helhum\Typo3Console\Service\Configuration\ConsoleRenderer\ConsoleRenderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationShowActiveCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Show active configuration value');
        $this->setHelp(
            <<<'EOH'
Shows active system configuration by path.
Shows the configuration value that is currently effective, no matter where and how it is set.

<b>Example:</b>

  <code>%command.full_name% DB --json</code>
EOH
        );
        $this->setDefinition([
            new InputArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to system configuration'
            ),
            new InputOption(
                'json',
                '',
                InputOption::VALUE_NONE,
                'If set, the configuration is shown as JSON'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $json = $input->getOption('json');
        $configurationService = new ConfigurationService();
        $consoleRenderer = new ConsoleRenderer();
        if (!$configurationService->hasActive($path)) {
            $output->writeln(sprintf('<error>No configuration found for path "%s"</error>', $path));

            return 1;
        }

        $active = $configurationService->getActive($path);
        $output->writeln($consoleRenderer->render($active, $json));

        return 0;
    }
}
