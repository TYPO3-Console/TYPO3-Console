<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Extension;

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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionActivateCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Activate extension(s)');
        $this->setHelp(
            <<<'EOH'
Activates one or more extensions by key.
Marks extensions as active, sets them up and clears caches for every activated extension.

<warning>This command is deprecated (and hidden) in Composer mode.</warning>
EOH
        );
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputArgument(
                'extensionKeys',
                InputArgument::REQUIRED,
                'Extension keys to activate. Separate multiple extension keys with comma.'
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensionKeys = explode(',', $input->getArgument('extensionKeys'));

        $this->showDeprecationMessageIfApplicable($output);
        (new ExtensionStateCommandsHelper($output))->activateExtensions($extensionKeys);
    }

    private function showDeprecationMessageIfApplicable(OutputInterface $output)
    {
        if (CompatibilityScripts::isComposerMode()) {
            $output->writeln('<warning>This command is deprecated when TYPO3 is composer managed.</warning>');
            $output->writeln('<warning>It might lead to unexpected results.</warning>');
            $output->writeln('<warning>The PackageStates.php file that tracks which extension should be active,</warning>');
            $output->writeln('<warning>should be generated automatically using install:generatepackagestates.</warning>');
            $output->writeln('<warning>To set up all active extensions, extension:setupactive should be used.</warning>');
            $output->writeln('<warning>This command will be disabled, when TYPO3 is composer managed, in TYPO3 Console 6</warning>');
        }
    }

    public function isHidden(): bool
    {
        $application = $this->getApplication();
        if (!$application instanceof Application || getenv('TYPO3_CONSOLE_RENDERING_REFERENCE')) {
            return true;
        }

        return !$application->isComposerManaged();
    }
}
