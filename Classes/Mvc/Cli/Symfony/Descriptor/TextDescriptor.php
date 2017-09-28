<?php
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
        if (null !== $argument->getDefault() && (!is_array($argument->getDefault()) || count($argument->getDefault()))) {
            $default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($argument->getDefault()));
        } else {
            $default = '';
        }

        $totalWidth = isset($options['total_width']) ? $options['total_width'] : Helper::strlen($argument->getName());
        $spacingWidth = $totalWidth - strlen($argument->getName());

        // + 4 = 2 spaces before <info>, 2 spaces after </info>
        $indent = $totalWidth + 4;
        $maxWidth = isset($options['screen_width']) ? ($options['screen_width'] - $indent) : null;
        $this->writeText(sprintf('  <info>%s</info>  %s%s%s',
            $argument->getName(),
            str_repeat(' ', $spacingWidth),
            $this->wordWrap($argument->getDescription(), $indent, $maxWidth),
            $default
        ), $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputOption(InputOption $option, array $options = [])
    {
        if ($option->acceptValue() && null !== $option->getDefault() && (!is_array($option->getDefault()) || count($option->getDefault()))) {
            $default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($option->getDefault()));
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

        $totalWidth = isset($options['total_width']) ? $options['total_width'] : $this->calculateTotalWidthForOptions([$option]);
        $synopsis = sprintf('%s%s',
            $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ',
            sprintf('--%s%s', $option->getName(), $value)
        );

        $spacingWidth = $totalWidth - Helper::strlen($synopsis);

        // + 4 = 2 spaces before <info>, 2 spaces after </info>
        $indent = $totalWidth + 4;
        $maxWidth = isset($options['screen_width']) ? ($options['screen_width'] - $indent) : null;
        $this->writeText(sprintf('  <info>%s</info>  %s%s%s%s',
            $synopsis,
            str_repeat(' ', $spacingWidth),
            $this->wordWrap($option->getDescription(), $indent, $maxWidth),
            $default,
            $option->isArray() ? '<comment> (multiple values allowed)</comment>' : ''
        ), $options);
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

    /**
     * Helper method to work around private method limitations in parent class
     * This avoids copying code that does not need to be changed.
     *
     * @param $methodName
     * @param array $arguments
     * @return mixed
     */
    public function __call($methodName, array $arguments)
    {
        $reflection = new \ReflectionClass(static::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this, $arguments);
    }
}
