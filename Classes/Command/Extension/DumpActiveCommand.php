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

use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DumpActiveCommand extends Command
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    public function __construct($name = null, PackageManager $packageManager = null)
    {
        parent::__construct($name);
        $this->packageManager = $packageManager ?: GeneralUtility::makeInstance(PackageManager::class);
    }

    protected function configure()
    {
        $this->setDescription('Mark selected TYPO3 core extensions and all third party extensions as active');
        $this->setHelp(
            <<<'EOF'
This command will write or update <code>typo3conf/PackageStates.php</code> file.
TYPO3 uses this file to track whether an extension is active in the system.
Goal is to not have this file in version control, but generate it automatically using the information
present in the root composer.json file (or given with the available options).

The following extensions will be marked as active:

- All third party extensions (present in typo3conf/ext/)
- All essential TYPO3 core extensions
- All TYPO3 core extensions which are provided with the <code>--framework-extensions</code> argument.
- In Composer managed TYPO3 systems, all composer dependencies to TYPO3 core extensions.

To require TYPO3 core extensions in your composer managed project use the following command:

<code>composer require typo3/cms-<exension-name> "<TYPO3-version>"</code>
EOF
);
        $this->addCommandOptions();
        $this->addDeprecatedCommandOptions();
    }

    private function addCommandOptions()
    {
        $this->addOption(
            'core-extension',
            '-c',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'TYPO3 core extension that should be marked as active.',
            []
        );
        $this->addOption(
            'exclude-extension',
            '-e',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Extension which should stay inactive. This does not affect provided core extensions or core extensions that are essential.',
            []
        );
        $this->addOption(
            'activate-default',
            '-d',
            InputOption::VALUE_NONE,
            sprintf(
                '%sIf true, <code>typo3/cms</code> extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured core extensions.',
                Bootstrap::usesComposerClassLoading() ? '(DEPRECATED for composer managed TYPO3)' . PHP_EOL : ''
            )
        );
    }

    /**
     * @deprecated in 5.0 will be removed in 6.0
     */
    private function addDeprecatedCommandOptions()
    {
        $this->addOption(
            'framework-extensions',
            null,
            InputOption::VALUE_OPTIONAL,
            '(DEPRECATED, use --core-extension instead) TYPO3 core extensions (comma separated) that should be marked as active.',
            []
        );
        $this->addOption(
            'excluded-extensions',
            null,
            InputOption::VALUE_OPTIONAL,
            '(DEPRECATED, use --exclude-extension instead) Extensions (comma separated) which should stay inactive. This does not affect provided core extensions or core extensions that are essential.',
            []
        );
    }

    /**
     * Hide deprecated options in help index and command reference
     *
     * @return InputDefinition
     * @deprecated in 5.0 will be removed in 6.0
     */
    public function getNativeDefinition()
    {
        $this->setDefinition(new InputDefinition());
        $this->addCommandOptions();
        return parent::getNativeDefinition();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleDeprecatedOptions($input, $output);

        $frameworkExtensions = $input->getOption('core-extension') ?: explode(',', (string)getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS'));
        $excludedExtensions = $input->getOption('exclude-extension');
        $activateDefault = $input->getOption('activate-default');
        if ($output instanceof ConsoleOutput && $activateDefault && Bootstrap::usesComposerClassLoading()) {
            // @deprecated for composer usage in 5.0 will be removed with 6.0
            $output->getErrorOutput()->writeln('<warning>Using --activate-default is deprecated in composer managed TYPO3 installations.</warning>');
            $output->getErrorOutput()->writeln('<warning>Instead of requiring typo3/cms in your project, you should consider only requiring individual packages you need.</warning>');
        }

        $packageStatesGenerator = new PackageStatesGenerator($this->packageManager);
        $activatedExtensions = $packageStatesGenerator->generate($frameworkExtensions, $excludedExtensions, $activateDefault);

        try {
            // Make sure file caches are empty after generating package states file
            CommandDispatcher::createFromCommandRun()->executeCommand('cache:flush', ['--files-only']);
        } catch (FailedSubProcessCommandException $e) {
            // Ignore errors here.
            // They might be triggered from extensions accessing db or having other things
            // broken in ext_tables or ext_localconf
            // In such case we cannot do much about it other than ignoring it for
            // generating packages states
        }

        $output->writeln(
            sprintf(
                '<info>The following extensions have been added to the generated PackageStates.php file:</info>%s%s',
                PHP_EOL,
                implode(', ', array_map(function (PackageInterface $package) {
                    return $package->getPackageKey();
                }, $activatedExtensions))
            )
        );
        if (!empty($excludedExtensions)) {
            $output->writeln(
                sprintf(
                    '<info>The following third party extensions were excluded during this process:</info> %s',
                    implode(', ', $excludedExtensions)
                )
            );
        }
    }

    /**
     * Handles deprecations and outputs warnings
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @deprecated in 5.0 will be removed in 6.0
     */
    private function handleDeprecatedOptions(InputInterface $input, OutputInterface $output)
    {
        $givenCommandName = $input->getArgument('command');
        if ($output instanceof ConsoleOutput && $givenCommandName === 'install:generatepackagestates') {
            $output->getErrorOutput()
                   ->writeln('<warning>Command name "install:generatepackagestates" is deprecated.</warning>');
            $output->getErrorOutput()
                   ->writeln(sprintf('<warning>Please use "%s" as command name instead.</warning>', $this->getName()));
        }
        if ($oldFrameworkOption = $input->getOption('framework-extensions')) {
            if ($output instanceof ConsoleOutput) {
                $output->getErrorOutput()
                       ->writeln('<warning>Option "--framework-extensions" is deprecated.</warning>');
                $output->getErrorOutput()
                       ->writeln('<warning>Please specify "--core-extension" for each extension instead.</warning>');
            }
            $input->setOption('core-extension', explode(',', $oldFrameworkOption));
        }
        if ($oldExcludeOption = $input->getOption('excluded-extensions')) {
            if ($output instanceof ConsoleOutput) {
                $output->getErrorOutput()
                       ->writeln('<warning>Option "--excluded-extensions" is deprecated.</warning>');
                $output->getErrorOutput()
                       ->writeln('<warning>Please specify "--exclude-extension" for each extension instead.</warning>');
            }
            $input->setOption('exclude-extension', explode(',', $oldExcludeOption));
        }
    }
}
