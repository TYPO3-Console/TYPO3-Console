<?php
namespace Helhum\Typo3Console\Tests\Functional\Command;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;

class HelpCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function callingNotExistingCommandReturnsErrorCode()
    {
        try {
            $this->commandDispatcher->executeCommand('foo');
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
        }
    }

    /**
     * @test
     */
    public function helpCanBeShown()
    {
        $output = $this->executeConsoleCommand('help');
        $this->assertContains('Usage:', $output);
    }

    /**
     * @test
     */
    public function autoCompleteDefaultsToBash()
    {
        $output = $this->executeConsoleCommand('autocomplete');
        $this->assertContains('#!/usr/bin/env bash', $output);
    }

    /**
     * @test
     */
    public function autoCompleteForBash()
    {
        $output = $this->executeConsoleCommand('autocomplete', ['shell' => 'bash']);
        $this->assertContains('#!/usr/bin/env bash', $output);
    }

    /**
     * @test
     */
    public function autoCompleteForZsh()
    {
        $output = $this->executeConsoleCommand('autocomplete', ['shell' => 'zsh']);
        $this->assertContains('#!/usr/bin/env zsh', $output);
    }

    /**
     * @test
     * @expectedException \Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException
     */
    public function wrongShellMakesAutoCompleteFail()
    {
        $this->commandDispatcher->executeCommand('autocomplete', ['shell' => 'wrong']);
    }
}
