<?php

namespace Helhum\Typo3Console\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class CacheServiceTest.
 */
class CacheServiceTest extends UnitTestCase
{
    /**
     * @var \Helhum\Typo3Console\Service\CacheService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    public function setup()
    {
        $this->subject = $this->getAccessibleMock('Helhum\\Typo3Console\\Service\\CacheService', ['getLogger']);
    }

    /**
     * Initializes configuration mock and sets the given configuration to the subject.
     *
     * @param array $mockedConfiguration
     */
    protected function setCacheConfiguration($mockedConfiguration)
    {
        $configurationManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
        $configurationManagerMock
            ->expects($this->atLeastOnce())
            ->method('getConfigurationValueByPath')
            ->will($this->returnValue($mockedConfiguration));
        $this->subject->_set('configurationManager', $configurationManagerMock);
    }

    /**
     * @test
     */
    public function cacheGroupsAreRetrievedCorrectlyFromConfiguration()
    {
        $this->setCacheConfiguration(
            [
                'cache_foo' => ['groups' => ['first', 'second']],
                'cache_bar' => ['groups' => ['third', 'second']],
                'cache_baz' => ['groups' => ['first', 'third']],
            ]
        );

        $expectedResult = [
            'first',
            'second',
            'third',
        ];

        $this->assertSame($expectedResult, $this->subject->getValidCacheGroups());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    public function flushByGroupThrowsExceptionForInvalidGroups()
    {
        $this->setCacheConfiguration(
            [
                'cache_foo' => ['groups' => ['first', 'second']],
                'cache_bar' => ['groups' => ['third', 'second']],
                'cache_baz' => ['groups' => ['first', 'third']],
            ]
        );

        $this->subject->flushGroups(['not', 'first']);
    }
}
