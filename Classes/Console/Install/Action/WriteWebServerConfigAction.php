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

use Composer\InstalledVersions;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Core\Core\Environment;

class WriteWebServerConfigAction implements InstallActionInterface
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var bool
     */
    private $skipAction = false;

    public function setOutput(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    public function setCommandDispatcher(CommandDispatcher $commandDispatcher)
    {
        // not needed
    }

    public function shouldExecute(array $actionDefinition, array $options = []): bool
    {
        return !$this->skipAction;
    }

    public function execute(array $actionDefinition, array $options = []): bool
    {
        $argumentDefinitions = $actionDefinition['arguments'] ?? [];
        $interactiveArguments = new InteractiveActionArguments($this->output);
        $arguments = $interactiveArguments->populate($argumentDefinitions, $options);
        if ($arguments['webServerConfig'] === 'none') {
            return true;
        }
        $publicPath = getenv('TYPO3_PATH_WEB');
        if (!$publicPath) {
            $publicPath = Environment::getPublicPath();
        }
        $sourcePath = $this->getInstallPackagePath() . '/Resources/Private/FolderStructureTemplateFiles';
        if ($arguments['webServerConfig'] === 'apache') {
            $source = $sourcePath . '/root-htaccess';
            $target = $publicPath . '/.htaccess';
        } else {
            $source = $sourcePath . '/root-web-config';
            $target = $publicPath . '/web.config';
        }

        if (file_exists($target)) {
            $this->output->outputLine('');
            $this->output->outputLine('<error>File does already exist.</error>');
            $this->output->outputLine('');

            $this->skipAction = true;

            return false;
        }

        return copy($source, $target);
    }

    private function getInstallPackagePath(): string
    {
        $installPackagePath = InstalledVersions::getInstallPath('typo3/cms-install');
        if ($installPackagePath !== null) {
            return $installPackagePath;
        }

        $rootPath = getenv('TYPO3_PATH_ROOT');
        if (!$rootPath) {
            $rootPath = Environment::getPublicPath();
        }

        return $rootPath . '/typo3/sysext/install';
    }
}
