<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Functional\Command\Documentation;

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

class GenerateXsdCommandTest extends AbstractCommandTest
{
    protected function setUp()
    {
        self::markTestSkipped('TODO: This command needs to be fixed or removed');
    }

    /**
     * @test
     */
    public function schemaForFluidCanBeGenerated()
    {
        $output = $this->executeConsoleCommand('documentation:generatexsd', ['TYPO3\\CMS\\Fluid\\ViewHelpers']);
        $this->assertContains('<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">', $output);
    }
}
