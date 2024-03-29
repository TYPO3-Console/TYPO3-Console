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
use Helhum\Typo3Console\Typo3CompatibilityBridge;
use TYPO3\CMS\Core\Core\Environment;

class PrepareInstallAction implements InstallActionInterface
{
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
        // Not needed here
    }

    public function shouldExecute(array $actionDefinition, array $options = []): bool
    {
        return true;
    }

    public function execute(array $actionDefinition, array $options = []): bool
    {
        $this->ensureInstallationIsPossible($options);

        // We don't use Environment API here, because this code is called from Composer plugins
        $typo3RootPath = getenv('TYPO3_PATH_ROOT');
        $firstInstallPath = $typo3RootPath . '/FIRST_INSTALL';
        touch($firstInstallPath);

        return true;
    }

    /**
     * Handles the case when system configuration file already exists
     *
     * @param array $options
     * @throws InstallationFailedException
     */
    private function ensureInstallationIsPossible(array $options)
    {
        $integrityCheck = $options['integrityCheck'] ?? false;
        if (!$integrityCheck) {
            return;
        }

        $isInteractive = $options['interactive'] ?? $this->output->getSymfonyConsoleInput()->isInteractive();
        $forceInstall = $options['forceInstall'] ?? false;

        $localConfFile = Typo3CompatibilityBridge::getSystemConfigurationFileLocation();
        if (!$forceInstall && file_exists($localConfFile)) {
            $this->output->outputLine();
            $this->output->outputLine('<error>TYPO3 seems to be already set up!</error>');
            $proceed = $isInteractive;
            if ($isInteractive) {
                $this->output->outputLine();
                $this->output->outputLine('<info>If you continue, your system configuration file</info>');
                $this->output->outputLine('<warning>will be deleted!</warning>');
                $this->output->outputLine();
                $proceed = $this->output->askConfirmation('<info>Do you really want to proceed?</info> (<comment>no</comment>) ', false);
            }
            if (!$proceed) {
                $this->output->outputLine('<error>Installation aborted!</error>');
                throw new InstallationFailedException('Installation aborted by user', 1529926774);
            }
        }
        @unlink($localConfFile);
        clearstatcache();
        if (file_exists($localConfFile)) {
            $this->output->outputLine();
            $this->output->outputLine('<error>Unable to delete configuration file!</error>');
            $this->output->outputLine('<error>Installation aborted!</error>');
            throw new InstallationFailedException('Installation aborted because of insufficient permissions', 1529926810);
        }
    }
}
