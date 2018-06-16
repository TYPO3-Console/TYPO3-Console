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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;

class PrepareInstallAction implements InstallActionInterface
{
    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @var ConsoleOutput
     */
    private $output;

    public function setOutput(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    public function setCommandDispatcher(CommandDispatcher $commandDispatcher)
    {
        $this->commandDispatcher = $commandDispatcher;
    }

    public function shouldExecute(array $actionDefinition, array $options = []): bool
    {
        return true;
    }

    public function execute(array $actionDefinition, array $options = []): bool
    {
        $typo3RootPath = rtrim(defined('PATH_site') ? PATH_site : getenv('TYPO3_PATH_ROOT'), '/');
        $firstInstallPath = $typo3RootPath . '/FIRST_INSTALL';
        if (!file_exists($firstInstallPath)) {
            touch($firstInstallPath);
        }

        if (isset($options['extensionSetup']) && !$options['extensionSetup']) {
            return true;
        }

        $packageStatesArguments = [];
        // Exclude all local extensions in case any are present, to avoid interference with the setup
        foreach (glob($typo3RootPath . '/typo3conf/ext/*') as $item) {
            $packageStatesArguments['--excluded-extensions'][] = basename($item);
        }
        $this->commandDispatcher->executeCommand('install:generatepackagestates', $packageStatesArguments);

        return true;
    }
}
