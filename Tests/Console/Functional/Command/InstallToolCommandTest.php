<?php

declare(strict_types=1);
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

class InstallToolCommandTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function installtoolCanBeLockedAndUnlocked()
    {
        $output = $this->executeConsoleCommand('install:unlock');
        $this->assertContains('Install Tool has been unlocked and can be accessed now at \'typo3/install.php\'', $output);
        $output = $this->executeConsoleCommand('install:unlock');
        $this->assertContains('Install Tool is already unlocked', $output);
        $output = $this->executeConsoleCommand('install:lock');
        $this->assertContains('Install Tool is locked and can not be accessed longer', $output);
        $output = $this->executeConsoleCommand('install:lock');
        $this->assertContains('Install Tool is already locked', $output);
    }
}
