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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Package\UncachedPackageManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstallGeneratePackageStatesCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Generate PackageStates.php file');
        $this->setHelp(
            <<<'EOH'
Generates and writes <code>typo3conf/PackageStates.php</code> file.
Goal is to not have this file in version control, but generate it on <code>composer install</code>.

Marks the following extensions as active:

- Third party extensions
- All core extensions that are required (or part of minimal usable system)
- All core extensions which are provided with the <code>--framework-extensions</code> argument.
- In composer mode all composer dependencies to TYPO3 framework extensions are detected and activated by default.

To require TYPO3 core extensions use the following command:

<code>composer require typo3/cms-foo "*"</code>

This updates your composer.json and composer.lock without any other changes.

<b>Example:</b>

  <code>%command.full_name%</code>
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
            new InputOption(
                'framework-extensions',
                null,
                InputOption::VALUE_REQUIRED,
                'TYPO3 system extensions that should be marked as active. Extension keys separated by comma.',
                []
            ),
            new InputOption(
                'excluded-extensions',
                null,
                InputOption::VALUE_REQUIRED,
                'Extensions which should stay inactive. This does not affect provided framework extensions or framework extensions that are required or part as minimal usable system.',
                []
            ),
            new InputOption(
                'activate-default',
                null,
                InputOption::VALUE_NONE,
                '(DEPRECATED) If true, `typo3/cms` extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.'
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
        $frameworkExtensions = $input->getOption('framework-extensions');
        $frameworkExtensions = is_array($frameworkExtensions)
            ? $frameworkExtensions : explode(',', $frameworkExtensions);
        $excludedExtensions = $input->getOption('excluded-extensions');
        $excludedExtensions = is_array($excludedExtensions)
            ? $excludedExtensions : explode(',', $excludedExtensions);
        $activateDefault = $input->getOption('activate-default');

        if ($activateDefault && CompatibilityScripts::isComposerMode()) {
            // @deprecated for composer usage in 5.0 will be removed with 6.0
            $output->writeln('<warning>Using --activate-default is deprecated in composer managed TYPO3 installations.</warning>');
            $output->writeln('<warning>Instead of requiring typo3/cms in your project, you should consider only requiring individual packages you need.</warning>');
        }
        $frameworkExtensions = $frameworkExtensions ?: explode(
            ',',
            (string)getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS')
        );
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        if (!$packageManager instanceof UncachedPackageManager) {
            throw new \RuntimeException('Expected UncachedPackageManager', 1576244721);
        }
        $packageManager->injectDependencyOrderingService(GeneralUtility::makeInstance(DependencyOrderingService::class));
        $packageStatesGenerator = new PackageStatesGenerator($packageManager);
        $activatedExtensions = $packageStatesGenerator->generate(
            $frameworkExtensions,
            $excludedExtensions,
            $activateDefault
        );

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

        $output->writeln(sprintf(
            '<info>The following extensions have been added to the generated PackageStates.php file:</info> %s',
            implode(', ', array_map(function (PackageInterface $package) {
                return $package->getPackageKey();
            }, $activatedExtensions))
        ));
        if (!empty($excludedExtensions)) {
            $output->writeln(sprintf(
                '<info>The following third party extensions were excluded during this process:</info> %s',
                implode(', ', $excludedExtensions)
            ));
        }
    }
}
