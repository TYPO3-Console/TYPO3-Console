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

class ListCommandTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function exampleCommandsAreProperlyRegistered(): void
    {
        $output = $this->executeConsoleCommand('list');
        $this->assertContains('ext:command', $output);
        $this->assertContains('ext_command:extension:list', $output);
    }

    /**
     * @test
     */
    public function serviceCommandGetsDependenciesInjected(): void
    {
        $output = $this->executeConsoleCommand('ext:command');
        $this->assertContains('injected', $output);
        $this->assertContains('full RunLevel', $output);
    }

    /**
     * @test
     */
    public function exampleCommandHasRunLevelSet(): void
    {
        $output = $this->executeConsoleCommand('ext_command:extension:list');
        $this->assertContains('no deps', $output);
        $this->assertContains('compile RunLevel', $output);
    }

    /**
     * @test
     */
    public function exampleCommandCanHaveOverriddenVendor(): void
    {
        $output = $this->executeConsoleCommand('ext_bla:extension:activate');
        $this->assertContains('no deps', $output);
        $this->assertContains('compile RunLevel', $output);
    }

    public function exampleCommandsHaveAliasesSetDataProvider(): array
    {
        return [
            'alias set in service definition' => [
                'ext:command-alias',
            ],
            'namespaced name implicitly set as alias' => [
                'ext_command:ext:alias',
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
        $this->assertContains('full RunLevel', $output);
    }
}
