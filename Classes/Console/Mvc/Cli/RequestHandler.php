<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

class RequestHandler
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function handle(Command $commandDefinition, ArgvInput $input = null, OutputInterface $output = null): Response
    {
        $input = $input ?: new ArgvInput();
        $output = $output ?: new SymfonyConsoleOutput();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $dispatcher = $this->objectManager->get(Dispatcher::class);

        $request = $this->buildRequest($input, $output, $commandDefinition);
        $response = new Response();
        $response->setInput($input);
        $response->setOutput($output);
        $dispatcher->dispatch($request, $response);

        return $response;
    }

    private function buildRequest(ArgvInput $input, OutputInterface $output, Command $commandDefinition)
    {
        $request = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\Request::class);
        $callingScript = $input->getFirstArgument();
        if ($callingScript !== $_SERVER['PHP_SELF']) {
            $callingScript = $_SERVER['PHP_SELF'] . ' ' . $callingScript;
        }
        $request->setCallingScript($callingScript);

        $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class)->setConfiguration(['extensionName' => $commandDefinition->getExtensionName()]);
        $request->setControllerObjectName($commandDefinition->getControllerClassName());
        $request->setControllerCommandName($commandDefinition->getControllerCommandName());

        $applicationDefinition = (new Application())->getDefinition();
        foreach ($commandDefinition->getArguments() as $argument) {
            $argumentName = $argument->getName();
            if ($input->hasGivenArgument($argumentName)) {
                $request->setArgument($argument->getName(), $input->getArgument($argumentName));
            }
            if ($input->hasGivenOption($argument->getOptionName())) {
                $output->writeln('<warning>Using named arguments is deprecated.</warning>');
                $output->writeln(sprintf('<warning>Gracefully setting argument "%s" for given option "%s".</warning>', $argument->getName(), $argument->getDashedName()));
                $request->setArgument($argument->getName(), $input->getOption($argument->getOptionName()));
            }
            $argumentName .= '_' . $commandDefinition->getCommandIdentifier();
            if ($input->hasArgument($argumentName) && $input->getArgument($argumentName) !== null) {
                $output->writeln(sprintf('<warning>Using "%s" as command argument name is deprecated.</warning>', $argument->getName()));
                $output->writeln('<warning>Please choose a different name for your argument.</warning>');
                $request->setArgument($argument->getName(), $input->getArgument($argumentName));
            }
        }
        foreach ($commandDefinition->getOptions() as $option) {
            // Handle option given as arguments
            // @deprecated in 5.0 will be removed in 6.0
            if ($input->hasGivenArgument($option->getName())) {
                $optionValue = $input->getArgument($option->getName());
                $request->setArgument($option->getName(), $optionValue);
                $output->writeln('<warning>Specifying argument values for options is deprecated.</warning>');
                $output->writeln(sprintf('<warning>Gracefully setting option "%s" to "%s".</warning>', $option->getDashedName(), $optionValue));
                continue;
            }

            // Handle option given as option (with or without value)
            if (!$input->hasGivenOption($option->getOptionName())) {
                continue;
            }

            $optionName = $option->getOptionName();
            if ($option->acceptsValue()) {
                $request->setArgument($option->getName(), $input->getOption($optionName));
            } else {
                $optionValue = $input->getOption($optionName);
                if ($optionValue === null) {
                    $optionValue = true;
                } elseif (!$commandDefinition->shouldValidateInputStrict() && !$applicationDefinition->hasOption($optionName)) {
                    $output->writeln('<warning>Using values for boolean options is deprecated.</warning>');
                    $output->writeln(sprintf('<warning>Gracefully setting option "%s" to "%s".</warning>', $option->getDashedName(), ($optionValue ? 'true' : 'false')));
                }
                $request->setArgument($option->getName(), (bool)$optionValue);
            }
        }

        return $request;
    }
}
