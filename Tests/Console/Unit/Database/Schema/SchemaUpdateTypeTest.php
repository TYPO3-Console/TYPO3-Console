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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

class SchemaUpdateTypeTest extends TestCase
{
    /**
     * @return array
     */
    public function expandTypesExpandsCorrectlyDataProvider()
    {
        return [
            'all' => [
                ['*'],
                [
                    'table.add',
                    'table.change',
                    'field.add',
                    'field.change',
                    'field.prefix',
                    'field.drop',
                    'table.prefix',
                    'table.drop',
                ],
            ],
            'all double' => [
                ['*', '*'],
                [
                    'table.add',
                    'table.change',
                    'field.add',
                    'field.change',
                    'field.prefix',
                    'field.drop',
                    'table.prefix',
                    'table.drop',
                ],
            ],
            'fields' => [
                ['field.*'],
                [
                    'field.add',
                    'field.change',
                    'field.prefix',
                    'field.drop',
                ],
            ],
            'tables' => [
                ['table.*'],
                [
                    'table.add',
                    'table.change',
                    'table.prefix',
                    'table.drop',
                ],
            ],
            'all add' => [
                ['*.add'],
                [
                    'table.add',
                    'field.add',
                ],
            ],
            'all change' => [
                ['*.change'],
                [
                    'table.change',
                    'field.change',
                ],
            ],
            'all prefix' => [
                ['*.prefix'],
                [
                    'field.prefix',
                    'table.prefix',
                ],
            ],
            'all drop' => [
                ['*.drop'],
                [
                    'field.drop',
                    'table.drop',
                ],
            ],
            'all safe' => [
                ['safe'],
                [
                    'table.add',
                    'table.change',
                    'field.add',
                    'field.change',
                ],
            ],
            'all destructive' => [
                ['destructive'],
                [
                    'field.prefix',
                    'field.drop',
                    'table.prefix',
                    'table.drop',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider expandTypesExpandsCorrectlyDataProvider
     * @param string[] $expandables
     * @param string[] $expected
     */
    public function expandTypesExpandsCorrectly(array $expandables, array $expected)
    {
        $updateTypes = array_map('strval', SchemaUpdateType::expandSchemaUpdateTypes($expandables));
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
     */
    public function expandTypesFailsForInvalidTypes($expandable)
    {
        $this->expectException(InvalidEnumerationValueException::class);
        SchemaUpdateType::expandSchemaUpdateTypes([$expandable]);
    }
}
