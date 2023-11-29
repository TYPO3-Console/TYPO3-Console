<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Frontend;

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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\PathUtility;

class FrontendAssetUrlCommand extends Command
{
    public function __construct(private readonly PackageManager $packageManager)
    {
        parent::__construct('frontend:asseturl');
    }

    protected function configure(): void
    {
        $this->setDescription('Show asset URL for TYPO3 extension(s)');
        $this->setHelp(
            <<<'EOH'
Shows public asset URLs for one or all TYPO3 extensions
EOH
        );
        $this->setDefinition([
            new InputOption(
                'extension',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Extension key',
            ),
            new InputOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (any of "table", "json", "text")',
                'table',
            ),
            new InputOption(
                'composer-name',
                'c',
                InputOption::VALUE_NONE,
                'Use composer name as index',
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $this->packageManager->getActivePackages();
        $packageKeys = $input->getOption('extension');
        if ($packageKeys !== []) {
            $packages = array_filter(
                $packages,
                static fn (string $packageKey) => in_array($packageKey, $packageKeys, true),
                ARRAY_FILTER_USE_KEY,
            );
        }

        $assetPaths = [];
        foreach ($packages as $package) {
            $packageKey = $input->getOption('composer-name') ? $package->getValueFromComposerManifest('name') : $package->getPackageKey();
            $resourcePath = sprintf('EXT:%s/Resources/Public/', $packageKey);
            if (!is_dir($this->packageManager->resolvePackagePath($resourcePath))) {
                continue;
            }
            $assetPaths[$packageKey] = '/' . PathUtility::getPublicResourceWebPath($resourcePath);
        }
        $format = $input->getOption('format');
        if ($format === 'table') {
            $this->renderAsTable($assetPaths, $output, $input->getOption('composer-name'));

            return Command::SUCCESS;
        }
        if ($format === 'json') {
            $output->writeln(json_encode($assetPaths, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }
        if ($format === 'text') {
            $this->renderAsText($assetPaths, $output);

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<error>Unknown format "%s"</error>', $format));

        return Command::FAILURE;
    }

    private function renderAsTable(array $assetPaths, OutputInterface $output, bool $useComposerName = false): void
    {
        $table = new Table($output);
        $table->setHeaders([$useComposerName ? 'composer name' : 'extension key', 'asset URL']);
        $lastKey = array_key_last($assetPaths);
        foreach ($assetPaths as $packageKey => $assetPath) {
            $table->addRow([$packageKey, $assetPath]);
            if ($packageKey !== $lastKey) {
                $table->addRow(new TableSeparator());
            }
        }
        $table->render();
    }

    private function renderAsText(array $assetPaths, OutputInterface $output): void
    {
        foreach ($assetPaths as $packageKey => $assetPath) {
            $output->writeln(sprintf('%s %s', $packageKey, $assetPath), OutputInterface::OUTPUT_RAW);
        }
    }
}
