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

use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Package\UncachedPackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstallGeneratePackageStatesCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Generate PackageStates.php file in non Composer enabled TYPO3 projects');
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
        $this->setDefinition([
            new InputOption(
                'framework-extensions',
                null,
                InputOption::VALUE_REQUIRED,
                'TYPO3 system extensions that should be marked as active. Extension keys separated by comma.'
            ),
            new InputOption(
                'excluded-extensions',
                null,
                InputOption::VALUE_REQUIRED,
                'Extensions which should stay inactive. This does not affect provided framework extensions or framework extensions that are required or part as minimal usable system.'
            ),
            new InputOption(
                'activate-default',
                null,
                InputOption::VALUE_NONE,
                '(DEPRECATED) If true, `typo3/cms` extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.'
            ),
        ]);
    }

    public function isHidden()
    {
        return !getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') && Environment::isComposerMode();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (Environment::isComposerMode()) {
            $output->writeln('<error>The command "install:generatepackagestates" is not available, because TYPO3 does not need this file any more in Composer mode.</error>');
            $output->writeln('<comment>For more details read: https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/11.4/Feature-94996-ConsiderAllComposerInstalledExtensionsAsActive.html</comment>');

            return 1;
        }

        $frameworkExtensions = $excludedExtensions = null;
        $activateDefault = $input->getOption('activate-default');
        if ($input->getOption('framework-extensions')) {
            $frameworkExtensions = explode(',', $input->getOption('framework-extensions'));
        } elseif (getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS')) {
            $frameworkExtensions = explode(
                ',',
                getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS')
            );
        }
        if ($input->getOption('excluded-extensions')) {
            $excludedExtensions = explode(',', $input->getOption('excluded-extensions'));
        }
        $dependencyOrderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $packageManager = GeneralUtility::makeInstance(UncachedPackageManager::class, $dependencyOrderingService);
        $packageStatesGenerator = new PackageStatesGenerator($packageManager);
        $activatedExtensions = $packageStatesGenerator->generate(
            $frameworkExtensions ?? [],
            $excludedExtensions ?? [],
            $activateDefault
        );

        try {
            // Make sure file caches are empty after generating package states file
            CommandDispatcher::createFromCommandRun()->executeCommand('cache:flush', ['--group', 'system']);
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

        return 0;
    }
}
