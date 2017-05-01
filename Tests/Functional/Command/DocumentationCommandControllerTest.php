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

class DocumentationCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function schemaForFluidCanBeGenerated()
    {
        $output = $this->commandDispatcher->executeCommand('documentation:generatexsd', ['--php-namespace' => 'TYPO3\\CMS\\Fluid\\ViewHelpers']);
        $this->assertContains('<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">', $output);
    }
}
