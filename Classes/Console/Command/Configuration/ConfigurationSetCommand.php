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

class ConfigurationSetCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Set configuration value');
        $this->setHelp(
            <<<'EOH'
Set system configuration option value by path.

<b>Examples:</b>

  <code>%command.full_name% SYS/fileCreateMask 0664</code>

  <code>%command.full_name% EXTCONF/processor_enabled true --json</code>

  <code>%command.full_name% EXTCONF/lang/availableLanguages '["de", "fr"]' --json</code>

  <code>%command.full_name% configuration:set BE/adminOnly -- -1</code>
EOH
        );
        $this->setDefinition([
            new InputArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to system configuration'
            ),
            new InputArgument(
                'value',
                InputArgument::REQUIRED,
                'Value for system configuration'
            ),
            new InputOption(
                'json',
                '',
                InputOption::VALUE_NONE,
                'Treat value as JSON (also makes it possible to force datatypes for value)'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $value = $input->getArgument('value');
        $json = $input->getOption('json');
        $configurationService = new ConfigurationService();

        if (!$configurationService->localIsActive($path)) {
            $output->writeln(sprintf(
                '<warning>It seems that configuration for path "%s" is overridden.</warning>',
                $path
            ));
            $output->writeln('<warning>Writing the new value might have no effect.</warning>');
        }

        $encodedValue = $value;
        if ($json) {
            $encodedValue = @json_decode($value, true);
        }

        if ($encodedValue === null && strtolower($value) !== 'null') {
            $output->writeln(sprintf('<error>Could not decode value "%s" as json.</error>', $value));

            return 2;
        }

        $setWasAllowed = $configurationService->setLocal($path, $encodedValue);
        $isApplied = $configurationService->hasLocal($path);

        if (!$setWasAllowed) {
            $output->writeln(sprintf(
                '<warning>Could not set value "%s" for configuration path "%s".</warning>',
                $value,
                $path
            ));
            $output->writeln('<warning>Possible reasons: configuration path is not allowed, configuration is not writable or type of value does not match given type.</warning>');

            return 1;
        }

        if ($isApplied) {
            $output->writeln(sprintf('<info>Successfully set value for path "%s".</info>', $path));
        } else {
            $output->writeln(sprintf(
                '<warning>Value "%s" for configuration path "%s" seems not applied.</warning>',
                $value,
                $path
            ));
            $output->writeln('<warning>Possible reasons: changed value in AdditionalConfiguration.php or extension ext_localconf.php</warning>');
        }

        return 0;
    }
}
