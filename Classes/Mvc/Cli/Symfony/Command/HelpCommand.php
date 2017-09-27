<?php
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

use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    }

    /**
     * Sets the command.
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->command) {
            $this->command = $this->getApplication()->find($input->getArgument('command_name'));
        }
        if ($this->command instanceof CommandControllerCommand) {
            // An Extbase command was originally called, but is now required to show the help information
            if ($isRaw = $input->getOption('raw')) {
                $output->setDecorated(false);
            }
            (new RequestHandler())->handle([$input->getFirstArgument(), 'help', $this->command->getName()], $input, $output);
        } else {
            // Any other Symfony command should just show up the regular info
            parent::execute($input, $output);
        }
    }
}
