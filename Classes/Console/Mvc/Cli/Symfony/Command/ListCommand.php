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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

class ListCommand extends Command
{
    protected function configure()
    {
        $this->setName('list');
        $this->setDefinition([
            new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name'),
            new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
            new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
            new InputOption('all', '-a', InputOption::VALUE_NONE, 'Show all commands, even the ones not available'),
        ]);
        $this->setDescription('List commands');
        $this->setHelp(
            <<<'EOF'
The <info>%command.name%</info> command lists all commands:

  <code>%command.full_name%</code>

You can also display the commands for a specific namespace:

  <code>%command.full_name% test</code>

You can also output the information in other formats by using the <comment>--format</comment> option:

  <code>%command.full_name% --format=xml</code>

It's also possible to get raw list of commands (useful for embedding command runner):

  <code>%command.full_name% --raw</code>
EOF
        );
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
            'namespace' => $input->getArgument('namespace'),
            'show_unavailable' => $input->getOption('all'),
            'screen_width' => (new Terminal())->getWidth() - 4,
        ]);
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            return 0;
        }
        $io = new SymfonyStyle($input, $output);
        $messages = [];
        if (!$input->getArgument('namespace') && !$application->isFullyCapable() && !$input->getOption('all')) {
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
        }
        $exceptions = [];
        if (($erroredCommands = $application->getErroredCommands()) && (!$application->hasErrors() || $input->getOption('all'))) {
            $messages[] = '';
            $messages[] = '<error>Some commands could not be configured and are missing in this list:</error>';
            foreach ($erroredCommands as $command) {
                $messages[] = sprintf(
                    '<error>Command name: "%s", error: "%s"</error>',
                    $command->getName(),
                    $command->getException()->getMessage()
                );
                $exceptions[] = $command->getException();
            }
        }
        if ($messages !== []) {
            $io->getErrorStyle()->writeln($messages);
        }
        if ($output->isVerbose()) {
            foreach ($exceptions as $exception) {
                $application->renderThrowable($exception, $output);
            }
        }

        return $exceptions === [] ? 0 : 1;
    }
}
