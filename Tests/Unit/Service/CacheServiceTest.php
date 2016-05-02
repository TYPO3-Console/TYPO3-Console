<?php
namespace Helhum\Typo3Console\Tests\Unit\Service;

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

use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class CacheServiceTest
 */
class CacheServiceTest extends UnitTestCase
{
    /**
     * @var \Helhum\Typo3Console\Service\CacheService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    public function setup()
    {
        $this->subject = $this->getAccessibleMock(\Helhum\Typo3Console\Service\CacheService::class, array('getLogger'));
    }

    /**
     * Initializes configuration mock and sets the given configuration to the subject
     *
     * @param array $mockedConfiguration
     */
    protected function setCacheConfiguration($mockedConfiguration)
    {
        $configurationServiceMock = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationServiceMock
            ->expects($this->atLeastOnce())
            ->method('getActive')
            ->will($this->returnValue($mockedConfiguration));
        $this->subject->_set('configurationService', $configurationServiceMock);
    }

    /**
     * @test
     */
    public function cacheGroupsAreRetrievedCorrectlyFromConfiguration()
    {
        $this->setCacheConfiguration(
            array(
                'cache_foo' => array('groups' => array('first', 'second')),
                'cache_bar' => array('groups' => array('third', 'second')),
                'cache_baz' => array('groups' => array('first', 'third')),
            )
        );

        $expectedResult = array(
            'first',
            'second',
            'third',
        );

        $this->assertSame($expectedResult, $this->subject->getValidCacheGroups());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    public function flushByGroupThrowsExceptionForInvalidGroups()
    {
        $this->setCacheConfiguration(
            array(
                'cache_foo' => array('groups' => array('first', 'second')),
                'cache_bar' => array('groups' => array('third', 'second')),
                'cache_baz' => array('groups' => array('first', 'third')),
            )
        );

        $this->subject->flushGroups(array('not', 'first'));
    }
}
