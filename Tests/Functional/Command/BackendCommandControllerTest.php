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

class BackendCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function backendCanBeLockedAndUnlocked()
    {
        $output = $this->commandDispatcher->executeCommand('backend:lock');
        $this->assertContains('Backend has been locked', $output);
        $output = $this->commandDispatcher->executeCommand('backend:lock');
        $this->assertContains('Backend is already locked', $output);
        $output = $this->commandDispatcher->executeCommand('backend:unlock');
        $this->assertContains('Backend lock is removed', $output);
        $output = $this->commandDispatcher->executeCommand('backend:unlock');
        $this->assertContains('Backend is already unlocked', $output);
    }

    /**
     * @test
     */
    public function backendCanBeLockedAndUnlockedForEditors()
    {
        $output = $this->commandDispatcher->executeCommand('backend:lockforeditors');
        $this->assertContains('Locked backend for editor access', $output);
        $output = $this->commandDispatcher->executeCommand('backend:lockforeditors');
        $this->assertContains('The backend was already locked for editors', $output);
        $output = $this->commandDispatcher->executeCommand('backend:unlockforeditors');
        $this->assertContains('Unlocked backend for editors', $output);
        $output = $this->commandDispatcher->executeCommand('backend:unlockforeditors');
        $this->assertContains('The backend was not locked for editors', $output);
    }
}
