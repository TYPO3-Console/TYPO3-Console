<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Mvc\Cli;

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

use Helhum\Typo3Console\Mvc\Cli\SubProcessException;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class SubProcessExceptionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function exceptionClassIsPutInDefaultExceptionMessage()
    {
        $subject = SubProcessException::createFromArray(
            [
                'class' => \Exception::class,
                'message' => 'Error',
                'code' => 4223,
                'trace' => [],
                'line' => 42,
                'file' => __FILE__,
                'previous' => null,
                'commandline' => 'typo3cms test',
                'output' => 'output',
                'error' => 'error output',
            ]
        );

        $this->assertSame('[Exception] Error', $subject->getMessage());
        $this->assertSame('Error', $subject->getPreviousExceptionMessage());
        $this->assertSame(4223, $subject->getCode());
        $this->assertSame(4223, $subject->getPreviousExceptionCode());
    }

    /**
     * @test
     */
    public function stringExceptionCodeIsPutInDefaultExceptionMessage()
    {
        $subject = SubProcessException::createFromArray(
            [
                'class' => \Exception::class,
                'message' => 'Error',
                'code' => '42S23',
                'trace' => [],
                'line' => 42,
                'file' => __FILE__,
                'previous' => null,
                'commandline' => 'typo3cms test',
                'output' => 'output',
                'error' => 'error output',
            ]
        );

        $this->assertSame('[Exception] [42S23] Error', $subject->getMessage());
        $this->assertSame('Error', $subject->getPreviousExceptionMessage());
        $this->assertSame(0, $subject->getCode());
        $this->assertSame('42S23', $subject->getPreviousExceptionCode());
    }
}
