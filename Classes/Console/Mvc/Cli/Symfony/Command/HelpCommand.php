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

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Helhum\Typo3Console\Mvc\Cli\Symfony\Descriptor\TextDescriptor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * Extends the help command of Symfony to show the specific help for Extbase commands
 */
class HelpCommand extends \Symfony\Component\Console\Command\HelpCommand
{
    /**
     * This needs to be re-set as the parent command has this property declared as "private" as well.
     *
     * @var Command
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setAliases([]);
        $this->setDescription('Display help for a command');
        $this->setHelp(
            <<<'EOF'
The <info>%command.name%</info> command displays help for a given command:

  <code>%command.full_name% list</code>

You can also output the help in other formats by using the <comment>--format</comment> option:

  <code>%command.full_name% --format=xml list</code>

To display the list of available commands, please use the <info>list</info> command.
EOF
        );
    }

    /**
     * We must ge hold of the command instance here, as the property is private
     * in the parent class.
     *
     * @param Command $command The command to set
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
        parent::setCommand($command);
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->command === null) {
            $this->command = $this->getApplication()->find($input->getArgument('command_name'));
        }

        $helper = new DescriptorHelper();
        // Add our own descriptor
        $helper->register('txt', new TextDescriptor());
        $helper->describe($output, $this->command, [
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'screen_width' => (new Terminal())->getWidth() - 4,
        ]);
        $this->command = null;

        return 0;
    }
}
