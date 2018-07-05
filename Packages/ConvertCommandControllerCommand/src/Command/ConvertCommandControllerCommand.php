<?php
declare(strict_types=1);
namespace Typo3Console\ConvertCommandControllerCommand\Command;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Exception;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\CommandControllerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertCommandControllerCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDefinition(
            new InputDefinition(
                [
                    new InputArgument(
                        'commandName',
                        InputArgument::IS_ARRAY,
                        'Convert these commands'
                    ),
                ]
            )
        )
        ->setDescription('Converts command controller commands');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($input->getArgument('commandName') as $commandName) {
            $configureCode = '';
            $command = $this->getApplication()->get($commandName);
            if (!$command instanceof CommandControllerCommand) {
                throw new Exception('Nope', 1530033958);
            }
            $configureCode .= $this->getDescriptionCode($command);
            $configureCode .= $this->getOptionDefinitionCode($command->getDefinition());
            $configureCode .= $this->getArgumentDefinitionCode($command->getDefinition());
            echo $configureCode;
        }
    }

    private function getDescriptionCode(Command $command)
    {
        $help = $command->getHelp();
        $description = addcslashes($command->getDescription(), '\'');

        return <<<EOD
\$this->setDescription('$description');
\$this->setHelp(<<<'EOH'
$help
EOH
);

EOD;
    }

    private function getOptionDefinitionCode(InputDefinition $inputDefinition)
    {
        $optionCodeTemplate = <<<'EOO'
                    new InputOption(
                        %s,
                        null,
                        %s,
                        %s,
                        %s
                    ),

EOO;
        $defineOptionsCode = ['               new InputDefinition([' . PHP_EOL];
        foreach ($inputDefinition->getOptions() as $option) {
            if ($this->getApplication()->getDefinition()->hasOption($option->getName())) {
                continue;
            }
            $modes = [];
            if ($option->isValueOptional()) {
                $modes[] = 'InputOption::VALUE_OPTIONAL';
            }
            if ($option->isValueRequired()) {
                $modes[] = 'InputOption::VALUE_REQUIRED';
            }
            if ($option->isArray()) {
                $modes[] = 'InputOption::VALUE_IS_ARRAY';
            }
            $defineOptionsCode[] = sprintf(
                $optionCodeTemplate,
                var_export($option->getName(), true),
                !empty($modes) ? implode(' | ', $modes) : 'null',
                var_export($option->getDescription(), true),
                var_export($option->getDefault(), true)
            );
        }
        $defineOptionsCode[] = '              ]);' . PHP_EOL;

        return implode(PHP_EOL, $defineOptionsCode);
    }

    private function getArgumentDefinitionCode(InputDefinition $inputDefinition)
    {
        $argumentCodeTemplate = <<<'EOO'
                    new InputArgument(
                        %s,
                        %s,
                        %s,
                        %s
                    ),

EOO;
        $defineArgumentsCode = ['               new InputDefinition([' . PHP_EOL];
        foreach ($inputDefinition->getArguments() as $argument) {
            $modes = [];
            if ($argument->isRequired()) {
                $modes[] = 'InputArgument::REQUIRED';
            }
            if ($argument->isArray()) {
                $modes[] = 'InputArgument::IS_ARRAY';
            }
            $defineArgumentsCode[] = sprintf(
                $argumentCodeTemplate,
                var_export($argument->getName(), true),
                !empty($modes) ? implode(' | ', $modes) : 'null',
                var_export($argument->getDescription(), true),
                var_export($argument->getDefault(), true)
            );
        }
        $defineArgumentsCode[] = '              ]);' . PHP_EOL;

        return implode(PHP_EOL, $defineArgumentsCode);
    }
}
