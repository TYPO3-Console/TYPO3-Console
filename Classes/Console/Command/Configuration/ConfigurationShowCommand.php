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
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationShowCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Show configuration value');
        $this->setHelp(
            <<<'EOH'
Shows system configuration value by path.
If the currently active configuration differs from the value in LocalConfiguration.php
the difference between these values is shown.

<b>Example:</b>

  <code>%command.full_name% DB</code>
EOH
        );
        $this->setDefinition([
            new InputArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to system configuration'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $configurationService = new ConfigurationService();
        $consoleRenderer = new ConsoleRenderer();

        $hasActive = $configurationService->hasActive($path);
        $hasLocal = $configurationService->hasLocal($path);

        if (!$hasActive && !$hasLocal) {
            $output->writeln(sprintf('<error>No configuration found for path "%s"</error>', $path));

            return 1;
        }

        $active = null;

        if ($hasActive) {
            $active = $configurationService->getActive($path);
        }

        if ($hasActive && $configurationService->localIsActive($path)) {
            $output->writeln($consoleRenderer->render($active));
        } else {
            $local = null;
            if ($hasLocal) {
                $local = $configurationService->getLocal($path);
            }
            $output->writeln($consoleRenderer->renderDiff($local, $active));
        }

        return 0;
    }
}
