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

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;

class ListCommandTest extends AbstractCommandTest
{
    public static function setUpBeforeClass(): void
    {
        self::installFixtureExtensionCode('ext_command');
    }

    public static function tearDownAfterClass(): void
    {
        self::removeFixtureExtensionCode('ext_command');
    }

    /**
     * @test
     */
    public function exampleCommandsAreProperlyRegistered(): void
    {
        $output = $this->executeConsoleCommand('list');
        self::assertStringContainsString('ext:alias', $output);
        self::assertStringContainsString('ext:command', $output);
    }

    /**
     * @test
     */
    public function errorsDuringBootDoNotPreventListToBeShown(): void
    {
        $output = $this->executeConsoleCommand('list', [], ['THROWS_LOCAL_CONF_EXCEPTION' => '1']);
        self::assertStringContainsString('cache:flush', $output);
        try {
            $this->commandDispatcher->executeCommand('list', ['--all'], ['THROWS_LOCAL_CONF_EXCEPTION' => '1']);
            $this->fail('Exit code is expected to be not 0');
        } catch (FailedSubProcessCommandException $e) {
            self::assertStringNotContainsString('ext:alias', $e->getOutputMessage());
            self::assertStringContainsString('ext:command', $e->getOutputMessage());
        }
    }

    /**
     * @test
     */
    public function errorsDuringBootThrowsExceptionWhenVerbose(): void
    {
        try {
            $this->commandDispatcher->executeCommand('list', ['--verbose'], ['THROWS_LOCAL_CONF_EXCEPTION' => '1', 'TYPO3_CONSOLE_SUB_PROCESS' => '0']);
            $this->fail('Exit code is expected to be not 0');
        } catch (FailedSubProcessCommandException $e) {
            self::assertStringNotContainsString('ext:alias', $e->getOutputMessage());
            self::assertStringNotContainsString('ext:command', $e->getOutputMessage());
            self::assertStringContainsString('1589036075', $e->getErrorMessage());
        }
    }

    /**
     * @test
     */
    public function commandCreationFailsButOtherCommandsAreStillShown(): void
    {
        try {
            $this->commandDispatcher->executeCommand('list', [], ['THROWS_CONSTRUCT_EXCEPTION' => '1']);
            $this->fail('Exit code is expected to be not 0');
        } catch (FailedSubProcessCommandException $e) {
            self::assertStringNotContainsString('ext:alias', $e->getOutputMessage());
            self::assertStringContainsString('Command name: "ext:alias", error: "Error occurred during object creation"', $e->getErrorMessage());
            self::assertStringNotContainsString('1589036051', $e->getErrorMessage());
            self::assertStringContainsString('ext:command', $e->getOutputMessage());
        }

        try {
            $this->commandDispatcher->executeCommand('list', ['--verbose'], ['THROWS_CONSTRUCT_EXCEPTION' => '1', 'TYPO3_CONSOLE_SUB_PROCESS' => '0']);
            $this->fail('Exit code is expected to be not 0');
        } catch (FailedSubProcessCommandException $e) {
            self::assertStringNotContainsString('ext:alias', $e->getOutputMessage());
            self::assertStringContainsString('Command name: "ext:alias", error: "Error occurred during object creation"', $e->getErrorMessage());
            self::assertStringContainsString('1589036051', $e->getErrorMessage());
            self::assertStringContainsString('ext:command', $e->getOutputMessage());
        }
    }

    /**
     * @test
     */
    public function commandsClassesAreNotInstantiatedTwiceWhenDisabled(): void
    {
        $output = $this->executeConsoleCommand('list', [], ['TYPO3_CONSOLE_TEST_RUN' => '0']);
        self::assertStringNotContainsString('ext:alias', $output);
        self::assertStringNotContainsString('ext:command', $output);
    }

    /**
     * @test
     */
    public function serviceCommandGetsDependenciesInjected(): void
    {
        $output = $this->executeConsoleCommand('ext:command');
        self::assertStringContainsString('injected', $output);
        self::assertStringContainsString('full RunLevel', $output);
    }

    public function exampleCommandsHaveAliasesSetDataProvider(): array
    {
        return [
            'alias set in service definition' => [
                'ext:command-alias',
            ],
            'first explicit alias works' => [
                'ext:alias1',
            ],
            'second explicit alias works' => [
                'ext2:alias',
            ],
        ];
    }

    /**
     * @param string $alias
     * @dataProvider exampleCommandsHaveAliasesSetDataProvider
     * @test
     */
    public function exampleCommandsHaveAliasesSet(string $alias): void
    {
        $output = $this->executeConsoleCommand($alias);
        self::assertStringContainsString('full RunLevel', $output);
    }
}
