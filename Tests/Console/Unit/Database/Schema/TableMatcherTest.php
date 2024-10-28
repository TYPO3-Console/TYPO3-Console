<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Database\Schema;

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

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Helhum\Typo3Console\Database\Schema\TableMatcher;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Information\Typo3Version;

class TableMatcherTest extends TestCase
{
    public function matchReturnsCorrectTableMatchesDataProvider(): array
    {
        return [
            'plain table name' => [
                'fe_sessions',
                [
                    'fe_sessions',
                ],
            ],
            'optionals' => [
                '[bf]e_sessions',
                [
                    'be_sessions',
                    'fe_sessions',
                ],
            ],
            'optionals inverted' => [
                '[!f]e_sessions',
                [
                    'be_sessions',
                ],
            ],
            'single char' => [
                '?e_sessions',
                [
                    'be_sessions',
                    'fe_sessions',
                ],
            ],
            'not existing plain table name' => [
                'foo_bar',
                [],
            ],
            'not matching placeholder' => [
                'foo_*',
                [],
            ],
            'placeholder at beginning' => [
                '*_sessions',
                [
                    'fe_sessions',
                    'be_sessions',
                ],
            ],
            'placeholder at end' => [
                'cf_*',
                [
                    'cf_foo',
                    'cf_foo_tags',
                    'cf_bar',
                    'cf_bar_tags',
                ],
            ],
            'placeholder in the middle' => [
                'cf_*_tags',
                [
                    'cf_foo_tags',
                    'cf_bar_tags',
                ],
            ],
            'multiple placeholders' => [
                '*_*_tags',
                [
                    'cache_foo_tags',
                    'cf_foo_tags',
                    'cf_bar_tags',
                ],
            ],
        ];
    }

    /**
     * @param string $expression
     * @param array $expectedMatches
     * @test
     * @dataProvider matchReturnsCorrectTableMatchesDataProvider
     */
    public function matchReturnsCorrectTableMatches(string $expression, array $expectedMatches)
    {
        $tables = [
            'cf_foo',
            'cf_foo_tags',
            'cf_bar',
            'cf_bar_tags',
            'cache_foo',
            'cache_foo_tags',
            'fe_sessions',
            'be_sessions',
        ];
        $connectionMock = $this->createMock(Connection::class);
        $schemaManagerMock = $this->createMock(AbstractSchemaManager::class);
        $schemaManagerMock->expects($this->atLeastOnce())->method('listTableNames')->willReturn($tables);
        if ((new Typo3Version())->getMajorVersion() > 12) {
            $connectionMock->expects($this->atLeastOnce())->method('createSchemaManager')->willReturn($schemaManagerMock);
        } else {
            $connectionMock->expects($this->atLeastOnce())->method('getSchemaManager')->willReturn($schemaManagerMock);
        }

        $tableMatcher = new TableMatcher();
        $matchedTables = $tableMatcher->match($connectionMock, $expression);

        foreach ($expectedMatches as $expectedMatch) {
            $this->assertTrue(in_array($expectedMatch, $matchedTables, true), sprintf('Expected table %s is not found in match result', $expectedMatch));
        }
        $this->assertCount(count($expectedMatches), $matchedTables, 'Count of expected matches is not the same as count of actual matches');
    }

    /**
     * @test
     */
    public function matchAllowsSpecifyingMultipleExpressions()
    {
        $tables = [
            'cf_foo',
            'cf_foo_tags',
            'cf_bar',
            'cf_bar_tags',
            'cache_foo',
            'cache_foo_tags',
            'fe_sessions',
            'be_sessions',
        ];
        $expectedMatches = [
            'cf_foo',
            'cf_foo_tags',
            'cf_bar',
            'cf_bar_tags',
            'cache_foo',
            'cache_foo_tags',
        ];
        $connectionMock = $this->createMock(Connection::class);
        $schemaManagerMock = $this->createMock(AbstractSchemaManager::class);
        $schemaManagerMock->expects($this->atLeastOnce())->method('listTableNames')->willReturn($tables);
        if ((new Typo3Version())->getMajorVersion() > 12) {
            $connectionMock->expects($this->atLeastOnce())->method('createSchemaManager')->willReturn($schemaManagerMock);
        } else {
            $connectionMock->expects($this->atLeastOnce())->method('getSchemaManager')->willReturn($schemaManagerMock);
        }

        $tableMatcher = new TableMatcher();
        $matchedTables = $tableMatcher->match($connectionMock, 'cf_*', 'cache_*');

        foreach ($expectedMatches as $expectedMatch) {
            $this->assertTrue(in_array($expectedMatch, $matchedTables, true), sprintf('Expected table %s is not found in match result', $expectedMatch));
        }
        $this->assertCount(count($expectedMatches), $matchedTables, 'Count of expected matches is not the same as count of actual matches');
    }
}
