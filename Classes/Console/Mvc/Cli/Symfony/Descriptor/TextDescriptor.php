<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Descriptor;

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

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Descriptor\Descriptor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * Text descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @internal
 */
class TextDescriptor extends \Symfony\Component\Console\Descriptor\TextDescriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeInputArgument(InputArgument $argument, array $options = [])
    {
        $totalWidth = isset($options['total_width']) ? $options['total_width'] : Helper::strlen($argument->getName());
        $spacingWidth = $totalWidth - strlen($argument->getName());

        // + 4 = 2 spaces before <info>, 2 spaces after </info>
        $indent = $totalWidth + 4;
        if (null !== $argument->getDefault() && (!is_array($argument->getDefault()) || count($argument->getDefault()))) {
            $default = "\n" . str_repeat(' ', $indent) . sprintf('<comment> • [default: %s]</comment>', $this->formatDefaultValue($argument->getDefault()));
        } else {
            $default = '';
        }

        $maxWidth = isset($options['screen_width']) ? ($options['screen_width'] - $indent) : null;
        $this->writeText(sprintf(
            '  <info>%s</info>  %s%s%s%s',
            $argument->getName(),
            str_repeat(' ', $spacingWidth),
            $this->wordWrap($argument->getDescription(), $indent, $maxWidth),
            $default,
            $argument->isArray() ? "\n" . str_repeat(' ', $indent) . '<comment> • (can be specified multiple times)</comment>' : ''
        ), $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputOption(InputOption $option, array $options = [])
    {
        $totalWidth = $options['total_width'] ?? $this->calculateTotalWidthForOptions([$option]);
        // + 4 = 2 spaces before <info>, 2 spaces after </info>
        $indent = $totalWidth + 4;

        if ($option->acceptValue() && null !== $option->getDefault() && (!is_array($option->getDefault()) || count($option->getDefault()))) {
            $default = "\n" . str_repeat(' ', $indent) . sprintf('<comment> • [default: %s]</comment>', $this->formatDefaultValue($option->getDefault()));
        } else {
            $default = '';
        }

        $value = '';
        if ($option->acceptValue()) {
            $value = '=' . strtoupper($option->getName());

            if ($option->isValueOptional()) {
                $value = '[' . $value . ']';
            }
        }

        $synopsis = sprintf(
            '%s%s',
            $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ',
            sprintf('--%s%s', $option->getName(), $value)
        );

        $spacingWidth = $totalWidth - Helper::strlen($synopsis);
        $maxWidth = isset($options['screen_width']) ? ($options['screen_width'] - $indent) : null;
        $this->writeText(sprintf(
            '  <info>%s</info>  %s%s%s%s',
            $synopsis,
            str_repeat(' ', $spacingWidth),
            $this->wordWrap($option->getDescription(), $indent, $maxWidth),
            $default,
            $option->isArray() ? "\n" . str_repeat(' ', $indent) . '<comment> • (can be specified multiple times)</comment>' : ''
        ), $options);
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function describeCommand(Command $command, array $options = [])
    {
        $command->getSynopsis(true);
        $command->getSynopsis(false);
        $command->mergeApplicationDefinition(false);

        $this->writeText('<comment>Usage:</comment>', $options);
        foreach (array_merge([$command->getSynopsis(true)], $command->getAliases(), $command->getUsages()) as $usage) {
            $this->writeText("\n");
            $this->writeText('  ' . $usage, $options);
        }
        $this->writeText("\n");

        $definition = $command->getNativeDefinition();

        if ($definition->getOptions() || $definition->getArguments()) {
            $this->writeText("\n");
            $this->describeInputDefinition($definition, $options);
            $this->writeText("\n");
        }

        if ($help = $command->getProcessedHelp()) {
            $this->writeText("\n");
            $this->writeText('<comment>Help:</comment>', $options);
            $this->writeText("\n");
            $this->writeText('  ' . str_replace("\n", "\n  ", $help), $options);
            $this->writeText("\n");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeApplication(Application $application, array $options = [])
    {
        $describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
        $description = new ApplicationDescription($application, $describedNamespace);

        if (isset($options['raw_text']) && $options['raw_text']) {
            $width = $this->getColumnWidth($description->getCommands());

            foreach ($description->getCommands() as $command) {
                $this->writeText(sprintf("%-{$width}s %s", $command->getName(), $command->getDescription()), $options);
                $this->writeText("\n");
            }
        } else {
            if ('' !== $help = $application->getHelp()) {
                $this->writeText("$help\n\n", $options);
            }

            $this->writeText("<comment>Usage:</comment>\n", $options);
            $this->writeText("  command [options] [arguments]\n\n", $options);

            $this->describeInputDefinition(new InputDefinition($application->getDefinition()->getOptions()), $options);

            $this->writeText("\n");
            $this->writeText("\n");

            $commands = $description->getCommands();
            $namespaces = $description->getNamespaces();
            if ($describedNamespace && $namespaces) {
                // make sure all alias commands are included when describing a specific namespace
                $describedNamespaceInfo = reset($namespaces);
                foreach ($describedNamespaceInfo['commands'] as $name) {
                    $commands[$name] = $description->getCommand($name);
                }
            }

            // calculate max. width based on available commands per namespace
            $width = $this->getColumnWidth(call_user_func_array('array_merge', array_map(function ($namespace) use ($commands) {
                return array_intersect($namespace['commands'], array_keys($commands));
            }, $namespaces)));

            if ($describedNamespace) {
                $this->writeText(sprintf('<comment>Available commands for the "%s" namespace:</comment>', $describedNamespace), $options);
            } else {
                $this->writeText('<comment>Available commands:</comment>', $options);
            }

            foreach ($namespaces as $namespace) {
                $namespace['commands'] = array_filter($namespace['commands'], function ($name) use ($commands) {
                    return isset($commands[$name]);
                });

                if (!$namespace['commands']) {
                    continue;
                }

                if (!$describedNamespace && ApplicationDescription::GLOBAL_NAMESPACE !== $namespace['id']) {
                    $this->writeText("\n");
                    $this->writeText(' <comment>' . $namespace['id'] . '</comment>', $options);
                }

                // Two spaces before <info>
                $indent = $width + 2;
                $maxWidth = isset($options['screen_width']) ? ($options['screen_width'] - $indent) : null;
                foreach ($namespace['commands'] as $name) {
                    $this->writeText("\n");
                    $spacingWidth = $width - Helper::strlen($name);
                    $command = $commands[$name];
                    $this->writeText(sprintf('  <info>%s</info>%s%s', $name, str_repeat(' ', $spacingWidth), $this->wordWrap($command->getDescription(), $indent, $maxWidth)), $options);
                }
            }

            $this->writeText("\n");
        }
    }

    /**
     * Wraps a text and adds indentation to new lines
     *
     * @param string $stringToWrap
     * @param int $indent
     * @param int|null $maxWidth
     * @return string
     */
    private function wordWrap(string $stringToWrap, int $indent, $maxWidth): string
    {
        $wrapped = $maxWidth === null ? $stringToWrap : wordwrap($stringToWrap, $maxWidth, "\n", true);

        return preg_replace('/\s*[\r\n]\s*/', "\n" . str_repeat(' ', $indent), $wrapped);
    }

    // Copied private methods

    /**
     * Formats input option/argument default value.
     *
     * @param mixed $default
     *
     * @return string
     */
    private function formatDefaultValue($default)
    {
        if (is_string($default)) {
            $default = OutputFormatter::escape($default);
        } elseif (is_array($default)) {
            foreach ($default as $key => $value) {
                if (is_string($value)) {
                    $default[$key] = OutputFormatter::escape($value);
                }
            }
        }

        return str_replace('\\\\', '\\', json_encode($default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * {@inheritdoc}
     */
    private function writeText($content, array $options = [])
    {
        $this->write(
            isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
            !isset($options['raw_output']) || !$options['raw_output']
        );
    }

    /**
     * @param (Command|string)[] $commands
     *
     * @return int
     */
    private function getColumnWidth(array $commands)
    {
        $widths = [];

        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $widths[] = Helper::strlen($command->getName());
                foreach ($command->getAliases() as $alias) {
                    $widths[] = Helper::strlen($alias);
                }
            } else {
                $widths[] = Helper::strlen($command);
            }
        }

        return $widths ? max($widths) + 2 : 0;
    }

    /**
     * @param InputOption[] $options
     *
     * @return int
     */
    private function calculateTotalWidthForOptions($options)
    {
        $totalWidth = 0;
        foreach ($options as $option) {
            // "-" + shortcut + ", --" + name
            $nameLength = 1 + max(Helper::strlen($option->getShortcut()), 1) + 4 + Helper::strlen($option->getName());

            if ($option->acceptValue()) {
                $valueLength = 1 + Helper::strlen($option->getName()); // = + value
                $valueLength += $option->isValueOptional() ? 2 : 0; // [ + ]

                $nameLength += $valueLength;
            }
            $totalWidth = max($totalWidth, $nameLength);
        }

        return $totalWidth;
    }
}
