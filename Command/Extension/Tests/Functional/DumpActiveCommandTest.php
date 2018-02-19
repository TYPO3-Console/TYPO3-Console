<?php
namespace Typo3Console\Command\Extension\Tests\Functional;

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

use Helhum\Typo3Console\Tests\Functional\Command\AbstractCommandTest;

class DumpActiveCommandTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function packageStatesFileIsCreatedWithoutDefaultPackages()
    {
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        @unlink($packageStatesFile);
        $this->executeConsoleCommand('extension:dumpactive');
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        $this->assertArrayNotHasKey('reports', $packageConfig['packages']);
    }

    /**
     * @test
     */
    public function packageStatesFileIsCreatedWithDefaultPackages()
    {
        $packageStatesFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/PackageStates.php';
        copy($packageStatesFile, $packageStatesFile . '_');
        @unlink($packageStatesFile);
        $this->executeConsoleCommand('extension:dumpactive', ['--activate-default']);
        $this->assertTrue(file_exists($packageStatesFile));
        $packageConfig = require $packageStatesFile;
        copy($packageStatesFile . '_', $packageStatesFile);
        $this->assertArrayHasKey('reports', $packageConfig['packages']);
    }
}
