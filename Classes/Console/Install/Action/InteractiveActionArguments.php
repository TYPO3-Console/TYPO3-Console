<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Action;

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

use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Exception\RuntimeException;

class InteractiveActionArguments
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var bool
     */
    private $isInteractive;

    /**
     * @var array
     */
    private $givenArguments;

    public function __construct(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    public function populate(array $argumentDefinitions, array $options = []): array
    {
        $this->isInteractive = $options['interactive'] ?? $this->output->getSymfonyConsoleInput()->isInteractive();
        $this->givenArguments = $options['givenArguments'];

        $actionArguments = [];
        foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
            $argumentValue = $this->extractArgumentValueFromDefinitionOrGivenArguments($argumentName, $argumentDefinition);
            while ($argumentValue === null) {
                $argumentValue = $this->fetchArgumentValue($argumentDefinition);
            }
            $actionArguments[$argumentName] = $argumentValue;
        }

        return $actionArguments;
    }

    private function extractArgumentValueFromDefinitionOrGivenArguments(string $argumentName, array $argumentDefinition)
    {
        $argumentValue = null;
        $isRequired = !isset($argumentDefinition['default']);
        if (!$this->isInteractive && !$isRequired) {
            $argumentValue = $argumentDefinition['default'];
        }

        if (isset($argumentDefinition['value'])) {
            $argumentValue = $argumentDefinition['value'];
        }

        if (isset($this->givenArguments[$argumentName])) {
            $argumentValue = $this->givenArguments[$argumentName];
        }

        if ($argumentValue === null && !$this->isInteractive) {
            throw new RuntimeException(
                sprintf(
                    'Option "%s" is not set, but is required and user interaction is disabled',
                    $argumentDefinition['option']
                ),
                1405273316
            );
        }

        return $argumentValue;
    }

    /**
     * @param $argumentDefinition
     * @return mixed
     */
    private function fetchArgumentValue(array $argumentDefinition)
    {
        $isRequired = !isset($argumentDefinition['default']);
        $argumentValue = null;

        switch ($argumentDefinition['type']) {
            case 'select':
                $argumentValue = $this->output->select(
                    sprintf(
                        '<comment>%s (%s):</comment> ',
                        $argumentDefinition['description'],
                        $isRequired ? 'required' : sprintf('default: "%s"', $argumentDefinition['default'])
                    ),
                    $argumentDefinition['values'],
                    $argumentDefinition['default'] ?? null,
                    false,
                    1
                );
                break;
            case 'string':
            case 'int':
                $argumentValue = $this->output->ask(
                    sprintf(
                        '<comment>%s (%s):</comment> ',
                        $argumentDefinition['description'],
                        $isRequired ? 'required' : sprintf('default: "%s"', $argumentDefinition['default'])
                    ),
                    $argumentDefinition['default'] ?? null
                );
                break;
            case 'bool':
                $argumentValue = (int)$this->output->askConfirmation(
                    sprintf(
                        '<comment>%s (%s):</comment> ',
                        $argumentDefinition['description'],
                        $argumentDefinition['default'] ? 'Y/n' : 'y/N'
                    ),
                    $argumentDefinition['default']
                );
                break;
            case 'hidden':
                $argumentValue = $this->output->askHiddenResponse(
                    sprintf(
                        '<comment>%s (%s):</comment> ',
                        $argumentDefinition['description'],
                        $isRequired ? 'required' : sprintf('default: "%s"', $argumentDefinition['default'])
                    )
                );
                if ($argumentValue === null) {
                    $argumentValue = $argumentDefinition['default'] ?? null;
                }
                break;
            default:
                throw new RuntimeException(sprintf('Unknown argument type "%s"', $argumentDefinition['type']), 1529154809);
        }

        return $argumentValue;
    }
}
