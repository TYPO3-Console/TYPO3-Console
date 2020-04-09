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

use Helhum\Typo3Console\Error\ExceptionRenderer;
use Helhum\Typo3Console\Mvc\Cli\SubProcessException;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Terminal;

class ExceptionRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function exceptionClassIsRenderedAsTitleMessageAsLinesAndCodeExplicitly()
    {
        $subject = new ExceptionRenderer($this->getTerminalStub());
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
        $subject->render(new \RuntimeException('Foo', 4223), $output);
        $renderedOutput = $output->fetch();
        $this->assertContains('[ RuntimeException ]', $renderedOutput);
        $this->assertContains('Foo', $renderedOutput);
        $this->assertContains('Exception code: 4223', $renderedOutput);
        $this->assertContains('Exception trace:', $renderedOutput);
    }

    /**
     * @test
     */
    public function exceptionCodeNotRenderedWhenNotVerbose()
    {
        $subject = new ExceptionRenderer($this->getTerminalStub());
        $output = new BufferedOutput();
        $subject->render(new \RuntimeException('Foo', 4223), $output);
        $renderedOutput = $output->fetch();
        $this->assertContains('[ RuntimeException ]', $renderedOutput);
        $this->assertContains('Foo', $renderedOutput);
        $this->assertNotContains('Exception code: 4223', $renderedOutput);
        $this->assertNotContains('Exception trace:', $renderedOutput);
    }

    /**
     * @test
     */
    public function exceptionCodeNotRenderedWhenEmpty()
    {
        $subject = new ExceptionRenderer($this->getTerminalStub());
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
        $subject->render(new \RuntimeException('Foo'), $output);
        $renderedOutput = $output->fetch();
        $this->assertContains('[ RuntimeException ]', $renderedOutput);
        $this->assertContains('Foo', $renderedOutput);
        $this->assertNotContains('Exception code:', $renderedOutput);
    }

    /**
     * @test
     */
    public function subProcessExceptionsRenderWithTheirRealClassName()
    {
        $subProcessException = SubProcessException::createFromArray(
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

        $subject = new ExceptionRenderer($this->getTerminalStub());
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
        $subject->render($subProcessException, $output);
        $renderedOutput = $output->fetch();

        $this->assertNotContains('SubProcessException', $renderedOutput);
        $this->assertContains('Exception code: 4223', $renderedOutput);
    }

    /**
     * @test
     */
    public function stringExceptionCodeForSubProcessExceptionsIsRenderedCorrectly()
    {
        $subProcessException = SubProcessException::createFromArray(
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

        $subject = new ExceptionRenderer($this->getTerminalStub());
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_VERBOSE);
        $subject->render($subProcessException, $output);
        $renderedOutput = $output->fetch();

        $this->assertContains('Exception code: 42S23', $renderedOutput);
    }

    private function getTerminalStub(): Terminal
    {
        return new class() extends Terminal {
            public function getWidth()
            {
                return 256;
            }

            public function getHeight()
            {
                return 256;
            }

            public static function hasSttyAvailable()
            {
                return false;
            }
        };
    }
}
