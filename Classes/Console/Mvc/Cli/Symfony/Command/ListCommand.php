<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Command;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Descriptor\TextDescriptor;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

class ListCommand extends \Symfony\Component\Console\Command\ListCommand
{
    protected function configure()
    {
        parent::configure();
        $this->amendDefinition($this->getDefinition());
    }

    public function getNativeDefinition()
    {
        return $this->amendDefinition(parent::getNativeDefinition());
    }

    /**
     * Subclass Symfony list command to be able to register our own text descriptor
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = new DescriptorHelper();
        $helper->register('txt', new TextDescriptor());
        $helper->describe($output, $this->getApplication(), [
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'show_unavailable' => $input->getOption('all'),
            'namespace' => $input->getArgument('namespace'),
            'screen_width' => (new Terminal())->getWidth() - 4,
        ]);
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            return 0;
        }
        if (!$input->getArgument('namespace') && !$application->isFullyCapable() && !$input->getOption('all')) {
            $outputHelper = new SymfonyStyle($input, $output);
            $messages = [
                '',
                sprintf(
                    '<comment>TYPO3 %s.</comment>',
                    $application->hasErrors() ? 'has errors' : 'is not fully set up'
                ),
                '<comment>Command list is reduced to only show low level commands.</comment>',
                sprintf(
                    '<comment>Not listed commands will not work until %s.</comment>',
                    $application->hasErrors() ? 'the errors are fixed' : 'TYPO3 is set up'
                ),
                sprintf(
                    '<comment>Run "%s --all" to list all commands.</comment>',
                    $_SERVER['PHP_SELF']
                ),
            ];

            $outputHelper->getErrorStyle()->writeln($messages);
        }

        return null;
    }

    private function amendDefinition(InputDefinition $definition): InputDefinition
    {
        $definition->addOption(new InputOption(
            'all',
            '-a',
            InputOption::VALUE_NONE,
            'Show all commands, even the ones not available'
        ));

        return $definition;
    }
}
