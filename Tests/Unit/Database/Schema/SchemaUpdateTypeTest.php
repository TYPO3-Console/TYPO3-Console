<?php
namespace Helhum\Typo3Console\Tests\Unit\Database\Schema;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class SchemaUpdateTypeTest
 */
class SchemaUpdateTypeTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function expandTypesExpandsCorrectlyDataProvider()
    {
        return [
            'all' => [
                '*',
                [
                    'field.add',
                    'field.change',
                    'field.prefix',
                    'field.drop',
                    'table.add',
                    'table.change',
                    'table.prefix',
                    'table.drop',
                    'table.clear',
                ]
            ],
            'fields' => [
                'field.*',
                [
                    'field.add',
                    'field.change',
                    'field.prefix',
                    'field.drop',
                ]
            ],
            'tables' => [
                'table.*',
                [
                    'table.add',
                    'table.change',
                    'table.prefix',
                    'table.drop',
                    'table.clear',
                ]
            ],
            'all add' => [
                '*.add',
                [
                    'field.add',
                    'table.add',
                ]
            ],
            'all change' => [
                '*.change',
                [
                    'field.change',
                    'table.change',
                ]
            ],
            'all prefix' => [
                '*.prefix',
                [
                    'field.prefix',
                    'table.prefix',
                ]
            ],
            'all drop' => [
                '*.drop',
                [
                    'field.drop',
                    'table.drop',
                ]
            ],
            'all clear' => [
                '*.clear',
                [
                    'table.clear',
                ]
            ],
            'all safe' => [
                'all.safe',
                [
                    'field.add',
                    'table.add',
                    'field.change',
                    'table.change',
                ]
            ],
            'all destructive' => [
                'all.destructive',
                [
                    'field.prefix',
                    'table.prefix',
                    'field.drop',
                    'table.drop',
                    'table.clear',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider expandTypesExpandsCorrectlyDataProvider
     * @param string $expandable
     * @param string[] $expected
     */
    public function expandTypesExpandsCorrectly($expandable, array $expected)
    {
        $updateTypes = array_map('strval', SchemaUpdateType::expandSchemaUpdateTypes([$expandable]));
        $this->assertSame($expected, $updateTypes);
    }
    /**
     * @return array
     */
    public function expandTypesFailsForInvalidTypesDataProvider()
    {
        return [
            'not known' => ['sadasd'],
            'not known with * at end' => ['sadasd.*'],
            'not known with * at beginning' => ['*.sadasd'],
            'all.*' => ['all.*'],
        ];
    }
    /**
     * @test
     * @dataProvider expandTypesFailsForInvalidTypesDataProvider
     * @param string $expandable
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     * @expectedExceptionCode 1439460396
     */
    public function expandTypesFailsForInvalidTypes($expandable)
    {
        SchemaUpdateType::expandSchemaUpdateTypes([$expandable]);
    }
}
