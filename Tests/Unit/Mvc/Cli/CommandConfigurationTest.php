<?php
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

use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use Helhum\Typo3Console\Tests\Unit\Fixtures\Command\TestCommandController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;

class CommandConfigurationTest extends TestCase
{
    public function validationThrowsExceptionOnInvalidRegistrationDataProvider()
    {
        return [
            'commands not an array' => [
                [
                    'commands' => '',
                ],
            ],
            'controllers not an array' => [
                [
                    'controllers' => '',
                ],
            ],
            'runLevels not an array' => [
                [
                    'runLevels' => '',
                ],
            ],
            'bootingSteps not an array' => [
                [
                    'bootingSteps' => '',
                ],
            ],
            'replace not an array' => [
                [
                    'replace' => '',
                ],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @test
     * @dataProvider validationThrowsExceptionOnInvalidRegistrationDataProvider
     */
    public function validationThrowsExceptionOnInvalidRegistration(array $configuration)
    {
        $this->expectException(RuntimeException::class);
        CommandConfiguration::ensureValidCommandRegistration($configuration, 'foo');
    }

    /**
     * @test
     */
    public function unifyCommandConfigurationMovesGlobalOptionsToCommandConfiguration()
    {
        $expected = [
            'bar:baz' => [
                'class' => 'bla',
                'vendor' => 'foobar',
                'runLevel' => 'normal',
                'bootingSteps' => ['one'],
                'replace' => ['replaced:command'],
            ],
        ];
        $actual = CommandConfiguration::unifyCommandConfiguration(
            [
                'commands' => [
                    'bar:baz' => [
                        'class' => 'bla',
                        'vendor' => 'foobar',
                    ],
                ],
                'runLevels' => [
                    'bar:baz' => 'normal',
                ],
                'bootingSteps' => [
                    'bar:baz' => ['one'],
                ],
                'replace' => [
                    'replaced:command',
                ],
            ],
            'foo'
        );

        $this->assertSame($expected, $actual);
    }

    public function unifyCommandConfigurationMovesGlobalRunLevelOptionsToCommandConfigurationDataProvider()
    {
        return [
            'command matches' => [
                'foo:bar',
                ['foo:bar' => 'normal'],
            ],
            'name spaced name in run level' => [
                'foo:bar',
                ['baz:foo:bar' => 'normal'],
            ],
            'name spaced collection in run level, command name not matching' => [
                'foo:bar',
                ['baz:foo:bla' => 'wrong', 'baz:foo:*' => 'normal'],
            ],
            'name spaced collection first in run level, command name not matching' => [
                'foo:bar',
                ['baz:foo:*' => 'normal', 'baz:foo:bla' => 'wrong'],
            ],
            'name spaced collection in run level, command name matching' => [
                'foo:bla',
                ['baz:foo:bla' => 'normal', 'baz:foo:*' => 'wrong'],
            ],
            'name spaced collection first in run level, command name matching' => [
                'foo:bla',
                ['baz:foo:*' => 'wrong', 'baz:foo:bla' => 'normal'],
            ],
        ];
    }

    /**
     * @param string $commandName
     * @param array $runLevels
     * @test
     * @dataProvider unifyCommandConfigurationMovesGlobalRunLevelOptionsToCommandConfigurationDataProvider
     */
    public function unifyCommandConfigurationMovesGlobalRunLevelOptionsToCommandConfiguration(string $commandName, array $runLevels)
    {
        $expected = [
            $commandName => [
                'vendor' => 'baz',
                'runLevel' => 'normal',
            ],
        ];
        $actual = CommandConfiguration::unifyCommandConfiguration(
            [
                'commands' => [
                    $commandName => [
                    ],
                ],
                'runLevels' => $runLevels,
            ],
            'baz'
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function commandControllerCommandsAreResolved()
    {
        $expected = [
            'test:hello' => [
                'vendor' => 'typo3_console',
                'controller' => TestCommandController::class,
                'controllerCommandName' => 'hello',
            ],
        ];
        $actual = CommandConfiguration::unifyCommandConfiguration(
            [
                'controllers' => [TestCommandController::class],
            ],
            ''
        );

        $this->assertSame($expected, $actual);
    }
}
