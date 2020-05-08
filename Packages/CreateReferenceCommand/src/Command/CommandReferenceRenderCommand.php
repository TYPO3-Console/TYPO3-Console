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

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.DocTools".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Helhum\Typo3Console\Mvc\Cli\CommandCollection;
use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Typo3Console\CreateReferenceCommand\EmptyTypo3CommandRegistry;

/**
 * "Command Reference" command controller for the Documentation package.
 * Used to create reference documentation for TYPO3 Console CLI commands.
 */
class CommandReferenceRenderCommand extends \Symfony\Component\Console\Command\Command
{
    private $skipCommands = [
        'server:run',
    ];

    public function isEnabled()
    {
        return getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') === false;
    }

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
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
        $_SERVER['PHP_SELF'] = Application::COMMAND_NAME;
        $commandReferenceDir = getenv('TYPO3_PATH_COMPOSER_ROOT') . '/Documentation/CommandReference/';
        GeneralUtility::flushDirectory($commandReferenceDir, true);
        $commandCollection = new CommandCollection(new CommandConfiguration(), new EmptyTypo3CommandRegistry());
        $application = new class($this->getApplication()) extends \Symfony\Component\Console\Application {
            /**
             * @var Application
             */
            private $application;

            public function __construct(Application $application, string $name = 'UNKNOWN', string $version = 'UNKNOWN')
            {
                parent::__construct($name, $version);
                $this->application = $application;
            }

            protected function getDefaultInputDefinition()
            {
                return $this->application->getDefaultInputDefinition();
            }

            protected function getDefaultCommands()
            {
                return $this->application->getDefaultCommands();
            }
        };
        $application->setCommandLoader($commandCollection);
        $applicationDescription = new ApplicationDescription($application, null, true);
        $commands = $applicationDescription->getCommands();
        $allCommands = [];
        foreach ($commands as $command) {
            if (in_array($command->getName(), $this->skipCommands, true)) {
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
                    'default' => str_replace(["\n", TYPO3_version], ['', '<Current-TYPO3-Version>'], var_export($option->getDefault(), true)),
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
                    if (isset($commands[$relatedCommandIdentifier])) {
                        $relatedCommand = $commands[$relatedCommandIdentifier];
                        $relatedCommands[$relatedCommandIdentifier] = $relatedCommand->getDescription();
                    } elseif (isset($commands[$shortCommandIdentifier])) {
                        $relatedCommand = $commands[$shortCommandIdentifier];
                        $relatedCommands[$shortCommandIdentifier] = $relatedCommand->getDescription();
                    } else {
                        $relatedCommands[$relatedCommandIdentifier] = '*Command not available*';
                    }
                }
            }

            $allCommands[$command->getName()] = [
                'identifier' => $command->getName(),
                'shortDescription' => $this->transformMarkup($command->getDescription()),
                'description' => $this->transformMarkup($command->getHelp() ? $command->getProcessedHelp() : ''),
                'options' => $optionDescriptions,
                'arguments' => $argumentDescriptions,
                'relatedCommands' => $relatedCommands,
                'docIdentifier' => str_replace(':', '-', $command->getName()),
                'docDirectory' => str_replace(':', '', ucwords($command->getName(), ':')),
            ];

            $standaloneView = new StandaloneView();
            $templatePathAndFilename = __DIR__ . '/../../Resources/Templates/CommandTemplate.txt';
            $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
            $standaloneView->assignMultiple(['command' => $allCommands[$command->getName()]]);

            $renderedOutputFile = $commandReferenceDir . $allCommands[$command->getName()]['docDirectory'] . '.rst';
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
                'commandName' => Application::COMMAND_NAME,
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
        $output = preg_replace('|\<b>(((?!\</b>).)*)\</b>|', '**$1**', $input);
        $output = preg_replace('|\<i>(((?!\</i>).)*)\</i>|', '*$1*', $output);
        $output = preg_replace('|\<u>(((?!\</u>).)*)\</u>|', '*$1*', $output);
        $output = preg_replace('|\<em>(((?!\</em>).)*)\</em>|', '*$1*', $output);
        $output = preg_replace('|\<comment>(((?!\</comment>).)*)\</comment>|', '**$1**', $output);
        $output = preg_replace('|\<warning>(((?!\</warning>).)*)\</warning>|', '**$1**', $output);
        $output = preg_replace('|\<strike>(((?!\</strike>).)*)\</strike>|', '[$1]', $output);
        $output = preg_replace('|\<code>(((?!\</code>).)*)\</code>|', '`$1`', $output);
        $output = preg_replace('|\<info>(((?!\</info>).)*)\</info>|', '`$1`', $output);

        return $output;
    }
}
