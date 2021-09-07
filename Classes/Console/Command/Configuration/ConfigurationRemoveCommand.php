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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigurationRemoveCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Remove configuration option');
        $this->setHelp(
            <<<'EOH'
Removes a system configuration option by path.

For this command to succeed, the configuration option(s) must be in
LocalConfiguration.php and not be overridden elsewhere.

<b>Example:</b>

  <code>%command.full_name% DB,EXT/EXTCONF/realurl</code>
EOH
        );
        $this->setDefinition([
            new InputArgument(
                'paths',
                InputArgument::REQUIRED,
                'Path to system configuration that should be removed. Multiple paths can be specified separated by comma'
            ),
            new InputOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'If set, does not ask for confirmation'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = explode(',', $input->getArgument('paths'));
        $force = $input->getOption('force');
        $io = new SymfonyStyle($input, $output);
        $configurationService = new ConfigurationService();
        foreach ($paths as $path) {
            if (!$configurationService->localIsActive($path)) {
                $io->writeln(sprintf(
                    '<warning>It seems that configuration for path "%s" is overridden.</warning>',
                    $path
                ));
                $io->writeln('<warning>Removing the new value might have no effect.</warning>');
            }
            if (!$force && $configurationService->hasLocal($path)) {
                $reallyDelete = $io->askQuestion(new ConfirmationQuestion('Remove ' . $path . ' from system configuration (TYPO3_CONF_VARS)?', false));
                if (!$reallyDelete) {
                    continue;
                }
            }
            $removed = $configurationService->removeLocal($path);
            if ($removed) {
                $io->writeln(sprintf(
                    '<info>Removed "%s" from system configuration.</info>',
                    $path
                ));
            } else {
                $io->writeln(sprintf(
                    '<warning>Path "%s" seems invalid or empty. Nothing done!</warning>',
                    $path
                ));
            }
        }

        return 0;
    }
}
