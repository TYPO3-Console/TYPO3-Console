<?php
declare(strict_types=1);
namespace Typo3Console\CreateReferenceCommand\Command;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * "Command Reference" command controller for the Documentation package.
 * Used to create reference documentation for TYPO3 Console CLI commands.
 */
class CommandReferenceRenderCommand extends Command
{
    private const consoleCommands = [
        'backend:createadmin',
        'backend:lockforeditors',
        'backend:unlockforeditors',
        'cache:flushtags',
        'cache:listgroups',
        'configuration:remove',
        'configuration:set',
        'configuration:show',
        'configuration:showactive',
        'configuration:showlocal',
        'database:export',
        'database:import',
        'database:updateschema',
        'frontend:request',
        'frontend:asseturl',
        'install:setup',
        'install:fixfolderstructure',
        'install:extensionsetupifpossible',
        'install:environmentandfolders',
        'install:databaseconnect',
        'install:databaseselect',
        'install:databasedata',
        'install:defaultconfiguration',
        'install:actionneedsexecution',
        'install:lock',
        'install:unlock',
        'help',
        'list',
    ];

    private $skipCommands = [
        'server:run',
        'help',
        'list',
    ];

    public function __construct(private readonly ContainerInterface $failsafeContainer, private readonly BootService $bootService)
    {
        parent::__construct('commandreference:render');
    }

    public function isEnabled(): bool
    {
        return getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') === false;
    }

    protected function configure(): void
    {
        $this->setDefinition(
            new InputDefinition(
                [
                    new InputArgument(
                        'skipCommands',
                        InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                        'Skip these commands from rendering',
                        []
                    ),
                ]
            )
        )
        ->setDescription('Renders command reference documentation from source code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->skipCommands = $input->getArgument('skipCommands') ?: $this->skipCommands;

        return $this->renderReference($output);
    }

    /**
     * Render a CLI command reference to reStructuredText.
     *
     * @param OutputInterface $output
     * @return int
     */
    protected function renderReference(OutputInterface $output): int
    {
        putenv('TYPO3_CONSOLE_RENDERING_REFERENCE=1');
        $_SERVER['PHP_SELF'] = 'typo3';
        $commandReferenceDir = getenv('TYPO3_PATH_COMPOSER_ROOT') . '/Documentation/CommandReference/';
        GeneralUtility::rmdir($commandReferenceDir, true);
        GeneralUtility::mkdir($commandReferenceDir);

        $container = $this->bootService->getContainer();
        $commandRegistry = $container->get(CommandRegistry::class);
        $application = $this->getApplication();
        assert($application instanceof Application);
        $allCommands = [];
        foreach ($commandRegistry->getNames() as $commandName) {
            if (in_array($commandName, $this->skipCommands, true)
                || !in_array($commandName, self::consoleCommands, true)
            ) {
                continue;
            }
            $command = $commandRegistry->get($commandName);
            if (!$command->isEnabled()) {
                continue;
            }

            $argumentDescriptions = [];
            $optionDescriptions = [];
            $command->mergeApplicationDefinition(false);
            $commandDefinition = $command->getNativeDefinition();
            foreach ($commandDefinition->getArguments() as $argument) {
                $argumentDescriptions[$argument->getName()] = $this->transformMarkup($argument->getDescription());
            }

            foreach ($commandDefinition->getOptions() as $option) {
                if ($application->getDefinition()->hasOption($option->getName())) {
                    // We don't want global options to be rendered multiple times in the reference
                    continue;
                }
                $optionDescriptions[$this->getOptionName($option)] = [
                    'description' => $this->transformMarkup($option->getDescription()),
                    'acceptValue' => $option->acceptValue() ? 'yes' : 'no',
                    'isValueRequired' => $option->isValueRequired() ? 'yes' : 'no',
                    'isMultiple' => $option->isArray() ? 'yes' : 'no',
                    'default' => str_replace(["\n", (string)(new Typo3Version())], ['', '<Current-TYPO3-Version>'], var_export($option->getDefault(), true)),
                ];
            }

            $relatedCommands = [];
            if ($command instanceof RelatableCommandInterface) {
                $relatedCommandIdentifiers = $command->getRelatedCommandNames();
                foreach ($relatedCommandIdentifiers as $relatedCommandIdentifier) {
                    $commandParts = explode(':', $relatedCommandIdentifier);
                    $shortCommandIdentifier = $relatedCommandIdentifier;
                    if (count($commandParts) === 3) {
                        $shortCommandIdentifier = $commandParts[1] . ':' . $commandParts[2];
                    }
                    if ($commandRegistry->has($relatedCommandIdentifier)) {
                        $relatedCommand = $commandRegistry->get($relatedCommandIdentifier);
                        $relatedCommands[$relatedCommandIdentifier] = str_replace(':', '-', $relatedCommand->getName());
                    } elseif ($commandRegistry->has($shortCommandIdentifier)) {
                        $relatedCommand = $commandRegistry->get($shortCommandIdentifier);
                        $relatedCommands[$shortCommandIdentifier] = str_replace(':', '-', $relatedCommand->getName());
                    } else {
                        $relatedCommands[$relatedCommandIdentifier] = '*Command not available*';
                    }
                }
            }
            $allCommands[$commandName] = [
                'identifier' => $commandName,
                'shortDescription' => $this->transformMarkup($command->getDescription()),
                'description' => $this->transformMarkup($command->getHelp() ? $command->getProcessedHelp() : ''),
                'options' => $optionDescriptions,
                'arguments' => $argumentDescriptions,
                'relatedCommands' => $relatedCommands,
                'docIdentifier' => str_replace(':', '-', $commandName),
                'docDirectory' => str_replace(':', '', ucwords($commandName, ':')),
            ];

            $standaloneView = new StandaloneView();
            $templatePathAndFilename = __DIR__ . '/../../Resources/Templates/CommandTemplate.txt';
            $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
            $standaloneView->assignMultiple(['command' => $allCommands[$commandName]]);

            $renderedOutputFile = $commandReferenceDir . $allCommands[$commandName]['docDirectory'] . '.rst';
            file_put_contents($renderedOutputFile, $standaloneView->render());
        }

        $applicationOptions = [];
        foreach ($application->getDefinition()->getOptions() as $option) {
            $applicationOptions[$this->getOptionName($option)] = [
                'description' => $this->transformMarkup($option->getDescription()),
                'acceptValue' => $option->acceptValue() ? 'yes' : 'no',
                'isValueRequired' => $option->isValueRequired() ? 'yes' : 'no',
                'isMultiple' => $option->isArray() ? 'yes' : 'no',
                'default' => str_replace("\n", '', var_export($option->getDefault(), true)),
            ];
        }

        $standaloneView = new StandaloneView();
        $templatePathAndFilename = __DIR__ . '/../../Resources/Templates/CommandReferenceTemplate.txt';
        $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
        $standaloneView->assignMultiple(
            [
                'title' => 'Command Reference',
                'commandName' => 'typo3',
                'applicationOptions' => $applicationOptions,
                'allCommandsByPackageKey' => ['typo3_console' => $allCommands],
            ]
        );

        $renderedOutputFile = getenv('TYPO3_PATH_COMPOSER_ROOT') . '/Documentation/CommandReference/Index.rst';
        file_put_contents($renderedOutputFile, $standaloneView->render());
        $output->writeln('DONE.');

        return 0;
    }

    private function getOptionName(InputOption $option)
    {
        $name = '--' . $option->getName();
        if ($option->getShortcut()) {
            $name .= '|-' . implode('|-', explode('|', $option->getShortcut()));
        }

        return $name;
    }

    /**
     * @param string $input
     * @return string
     */
    private function transformMarkup($input): string
    {
        $output = $input;
        // replace multiline comments with a code-block
        $output = preg_replace('/^(\s*\<code\>)(.*)(\<\/code\>\s*)$/m', "\n\n.. code-block:: shell\n\n   $2\n", $output);
        $output = preg_replace('|\<b>(((?!\</b>).)*)\</b>|', '**$1**', $output);
        $output = preg_replace('|\<i>(((?!\</i>).)*)\</i>|', '*$1*', $output);
        $output = preg_replace('|\<u>(((?!\</u>).)*)\</u>|', '*$1*', $output);
        $output = preg_replace('|\<em>(((?!\</em>).)*)\</em>|', '*$1*', $output);
        $output = preg_replace('|\<comment>(((?!\</comment>).)*)\</comment>|', '**$1**', $output);
        $output = preg_replace('|\<warning>(((?!\</warning>).)*)\</warning>|', '**$1**', $output);
        $output = preg_replace('|\<strike>(((?!\</strike>).)*)\</strike>|', '[$1]', $output);
        // replace inline code-blocks
        $output = preg_replace('|\<code>(((?!\</code>).)*)\</code>|', '`$1`', $output);
        $output = preg_replace('|\<info>(((?!\</info>).)*)\</info>|', '`$1`', $output);

        return $output;
    }
}
