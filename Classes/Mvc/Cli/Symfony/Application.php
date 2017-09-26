<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony;

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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    const TYPO3_CONSOLE_VERSION = '5.0.0';

    /**
     * @var RunLevel
     */
    private $runLevel;

    public function __construct(RunLevel $runLevel)
    {
        parent::__construct('TYPO3 Console', self::TYPO3_CONSOLE_VERSION);
        $this->runLevel = $runLevel;
    }

    public function hasAllCapabilities(): bool
    {
        return $this->runLevel->getMaximumAvailableRunLevel() === RunLevel::LEVEL_FULL;
    }

    public function isCommandAvailable(string $commandName): bool
    {
        return $this->runLevel->isCommandAvailable($commandName);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);
        $output->getFormatter()->setStyle('b', new OutputFormatterStyle(null, null, ['bold']));
        $output->getFormatter()->setStyle('i', new OutputFormatterStyle('black', 'white'));
        $output->getFormatter()->setStyle('u', new OutputFormatterStyle(null, null, ['underscore']));
        $output->getFormatter()->setStyle('em', new OutputFormatterStyle(null, null, ['reverse']));
        $output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, ['conceal']));
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
        $output->getFormatter()->setStyle('ins', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('del', new OutputFormatterStyle('red'));
        $output->getFormatter()->setStyle('code', new OutputFormatterStyle(null, null, ['bold']));
    }
}
