<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Service;

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

use Helhum\Typo3Console\Service\CacheService;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class CacheServiceTest extends UnitTestCase
{
    /**
     * @var \Helhum\Typo3Console\Service\CacheService
     */
    protected $subject;

    /**
     * Initializes configuration mock and sets the given configuration to the subject
     *
     * @param array $mockedConfiguration
     */
    protected function createCacheManagerWithConfiguration($mockedConfiguration)
    {
        $configurationServiceMock = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationServiceMock
            ->expects($this->atLeastOnce())
            ->method('getActive')
            ->will($this->returnValue($mockedConfiguration));
        $this->subject = new CacheService($configurationServiceMock);
    }

    /**
     * @test
     */
    public function cacheGroupsAreRetrievedCorrectlyFromConfiguration()
    {
        $this->createCacheManagerWithConfiguration(
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
        $this->createCacheManagerWithConfiguration(
            [
                'cache_foo' => ['groups' => ['first', 'second']],
                'cache_bar' => ['groups' => ['third', 'second']],
                'cache_baz' => ['groups' => ['first', 'third']],
            ]
        );

        $this->subject->flushGroups(['not', 'first']);
    }
}
