<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Install;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallActionNeedsExecutionCommand extends Command
{
    use ExecuteActionWithArgumentsTrait;

    protected function configure()
    {
        $this->setDescription('Calls needs execution on the given action and returns the result');
        $this->addArgument(
            'actionName',
            InputArgument::REQUIRED
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $actionName = $input->getArgument('actionName');

        $this->executeActionWithArguments($actionName, [], true);
    }
}
