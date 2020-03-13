<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Core\Booting;

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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RunLevelTest extends TestCase
{
    public function runLevelIsCorrectlyDeterminedForCommandsDataProvider()
    {
        return [
            'no namespace command' => [
                [
                    'foo' => RunLevel::LEVEL_COMPILE,
                ],
                'foo',
                RunLevel::LEVEL_COMPILE,
            ],
            'Definition same as name' => [
                [
                    'foo:bar' => RunLevel::LEVEL_COMPILE,
                ],
                'foo:bar',
                RunLevel::LEVEL_COMPILE,
            ],
            'Definition not present defaults to full' => [
                [],
                'foo:bar',
                RunLevel::LEVEL_FULL,
            ],
            'Command with package name works' => [
                [
                    'package:foo:bar' => RunLevel::LEVEL_COMPILE,
                ],
                'package:foo:bar',
                RunLevel::LEVEL_COMPILE,
            ],
            'Wildcard with package name works' => [
                [
                    'package:foo:*' => RunLevel::LEVEL_COMPILE,
                ],
                'package:foo:bar',
                RunLevel::LEVEL_COMPILE,
            ],
            'Wildcard first works for wildcard command' => [
                [
                    'package:foo:*' => RunLevel::LEVEL_COMPILE,
                    'package:foo:baz' => RunLevel::LEVEL_MINIMAL,
                ],
                'package:foo:bar',
                RunLevel::LEVEL_COMPILE,
            ],
            'Wildcard first works for specific command' => [
                [
                    'package:foo:*' => RunLevel::LEVEL_COMPILE,
                    'package:foo:baz' => RunLevel::LEVEL_MINIMAL,
                ],
                'package:foo:baz',
                RunLevel::LEVEL_MINIMAL,
            ],
            'Wildcard last works for wildcard command' => [
                [
                    'package:foo:baz' => RunLevel::LEVEL_MINIMAL,
                    'package:foo:*' => RunLevel::LEVEL_COMPILE,
                ],
                'package:foo:bar',
                RunLevel::LEVEL_COMPILE,
            ],
            'Wildcard last works for specific command' => [
                [
                    'package:foo:baz' => RunLevel::LEVEL_MINIMAL,
                    'package:foo:*' => RunLevel::LEVEL_COMPILE,
                ],
                'package:foo:baz',
                RunLevel::LEVEL_MINIMAL,
            ],
            'Wildcard without package name works' => [
                ['foo:*' => RunLevel::LEVEL_COMPILE],
                'foo:bar',
                RunLevel::LEVEL_COMPILE,
            ],
        ];
    }

    /**
     * @param array $definitions
     * @param string $requestedCommand
     * @param string $expectedRunlevel
     * @test
     * @dataProvider runLevelIsCorrectlyDeterminedForCommandsDataProvider
     */
    public function runLevelIsCorrectlyDeterminedForCommands(array $definitions, string $requestedCommand, string $expectedRunlevel)
    {
        $containerMock = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $subject = new RunLevel($containerMock, null);
        foreach ($definitions as $definition => $runLevel) {
            $subject->setRunLevelForCommand($definition, $runLevel);
        }

        $this->assertSame($expectedRunlevel, $subject->getRunLevelForCommand($requestedCommand));
    }
}
