<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @deprecated Will be removed with 6.0
 */
abstract class AbstractConvertedCommand extends Command
{
    private $synopsis = [];

    public function getNativeDefinition()
    {
        $definition = new InputDefinition($this->createNativeDefinition());
        $definition->addOptions($this->getApplication()->getDefinition()->getOptions());

        return $definition;
    }

    public function getSynopsis($short = false)
    {
        $key = $short ? 'short' : 'long';

        if (!isset($this->synopsis[$key])) {
            $this->synopsis[$key] = trim(sprintf('%s %s', $this->getName(), (new InputDefinition($this->createNativeDefinition()))->getSynopsis($short)));
        }

        return $this->synopsis[$key];
    }

    protected function createCompleteInputDefinition()
    {
        return array_merge($this->createNativeDefinition(), $this->createDeprecatedDefinition());
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $messages = null;
        $deprecatedDefinition = new InputDefinition($this->createDeprecatedDefinition());
        $nativeDefinition = new InputDefinition($this->createNativeDefinition());
        foreach ($deprecatedDefinition->getOptions() as $option) {
            $dashedName = $option->getName();
            $casedName = $this->casedFromDashed($dashedName);
            if (!$nativeDefinition->hasArgument($casedName)) {
                continue;
            }
            if ($input instanceof ArgvInput
                && $input->hasGivenOption($dashedName)
                && ($deprecatedValue = $input->getOption($dashedName)) !== null
            ) {
                $messages[] = '<warning>Using named arguments is deprecated.</warning>';
                $messages[] = sprintf('<warning>Gracefully setting argument "%s" for given option "%s".</warning>', $casedName, $dashedName);
                $input->setArgument($casedName, $deprecatedValue);
            }
        }
        foreach ($deprecatedDefinition->getArguments() as $argument) {
            $casedName = $argument->getName();
            $dashedName = $this->dashedFromCased($casedName);
            if (!$nativeDefinition->hasOption($dashedName)) {
                continue;
            }
            if ($input instanceof ArgvInput
                && $input->hasGivenArgument($casedName)
                && ($deprecatedValue = $input->getArgument($casedName)) !== null
            ) {
                $messages[] = '<warning>Specifying argument values for options is deprecated.</warning>';
                $messages[] = sprintf('<warning>Gracefully setting option "%s" to "%s".</warning>', $dashedName, $deprecatedValue);
                $input->setOption($dashedName, $deprecatedValue);
            }
        }

        if ($messages !== null) {
            $io = new SymfonyStyle($input, $output);
            $io->getErrorStyle()->writeln($messages);
        }

        $this->handleDeprecatedArgumentsAndOptions($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $definition = $this->getDefinition();
        $givenArguments = array_filter($input->getArguments(), function ($argumentName) use ($input) {
            return $input->hasGivenArgument($argumentName);
        }, ARRAY_FILTER_USE_KEY);
        $missingArguments = array_filter(array_keys($definition->getArguments()), function ($argument) use ($definition, $givenArguments) {
            return !array_key_exists($argument, $givenArguments) && $definition->getArgument($argument)->isRequired();
        });

        $argumentValue = null;
        $io = new SymfonyStyle($input, $output);
        foreach ($missingArguments as $missingArgument) {
            while ($argumentValue === null) {
                $argumentValue = $io->ask(sprintf('Please specify the required argument "%s"', $missingArgument));
            }
            $input->setArgument($missingArgument, $argumentValue);
            $argumentValue = null;
        }
    }

    abstract protected function createNativeDefinition(): array;

    protected function createDeprecatedDefinition(): array
    {
        $nativeDefinition = new InputDefinition($this->createNativeDefinition());
        $deprecatedDefinition = [];
        foreach ($nativeDefinition->getOptions() as $option) {
            $dashedName = $option->getName();
            $casedName = $this->casedFromDashed($dashedName);
            $deprecatedDefinition[] = new InputArgument(
                $casedName,
                InputArgument::OPTIONAL,
                $option->getDescription(),
                $option->getDefault()
            );
        }
        foreach ($nativeDefinition->getArguments() as $argument) {
            $casedName = $argument->getName();
            $dashedName = $this->dashedFromCased($casedName);
            $deprecatedDefinition[] = new InputOption(
                $dashedName,
                null,
                InputOption::VALUE_REQUIRED,
                $argument->getDescription(),
                $argument->getDefault()
            );
        }

        return $deprecatedDefinition;
    }

    abstract protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output);

    private function dashedFromCased(string $casedName): string
    {
        return mb_strtolower(preg_replace('/(?<=\\w)([A-Z])/', '-\\1', $casedName));
    }

    private function casedFromDashed(string $dashedName): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $dashedName))));
    }
}
