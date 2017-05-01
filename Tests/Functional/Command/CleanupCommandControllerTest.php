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

class CleanupCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function referenceIndexIsUpdated()
    {
        $output = $this->commandDispatcher->executeCommand('cleanup:updatereferenceindex');
        $this->assertContains('Updating reference index', $output);
    }
}
