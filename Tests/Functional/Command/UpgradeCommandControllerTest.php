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

class UpgradeCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function canCheckExtensionConstraints()
    {
        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints');
        $this->assertContains('All third party extensions claim to be compatible with TYPO3 version', $output);
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsReturnsErrorCodeOnFailure()
    {
        $this->installFixtureExtensionCode('ext_test');
        $this->commandDispatcher->executeCommand('extension:activate', ['--extension-keys' => 'ext_test']);
        try {
            $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--typo3-version' => '3.6.0']);
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
            $this->assertContains('"ext_test" requires TYPO3 versions 4.5.0', $e->getErrorMessage());
        }
        $this->commandDispatcher->executeCommand('extension:deactivate', ['--extension-keys' => 'ext_test']);
        $this->removeFixtureExtensionCode('ext_test');
    }

    /**
     * @test
     */
    public function checkExtensionConstraintsIssuesWarningForInvalidExtensionKeys()
    {
        $output = $this->commandDispatcher->executeCommand('upgrade:checkextensionconstraints', ['--extension-keys' => 'foo,bar']);
        $this->assertContains('Extension "foo" is not found in the system', $output);
        $this->assertContains('Extension "bar" is not found in the system', $output);
    }
}
