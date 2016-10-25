<?php
/**
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 */
namespace Helhum\Typo3Console\Tests\Unit\Service\Configuration;

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
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class ConfigurationServiceTest
 */
class ConfigurationServiceTest extends UnitTestCase
{
    /**
     * @var ConfigurationService
     */
    protected $subject;

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $activeConfiguration = [
        'main' => [
            'default' => 'value',
            'bla' => 'blupp',
            'baz' => 'bah',
            'foo' => 'bar'
        ]
    ];

    public function setup()
    {
        $this->configurationManager = $this->getMock(ConfigurationManager::class);
        $this->configurationManager->expects($this->any())
            ->method('getLocalConfiguration')
            ->willReturn([
                'main' => [
                    'bla' => 'blupp',
                    'bazz' => 'buh',
                    'foo' => 'baz'
                ]
            ]);
        $this->configurationManager->expects($this->any())
            ->method('getDefaultConfiguration')
            ->willReturn([
                'main' => [
                    'default' => 'value',
                ]
            ]);
        $this->subject = new ConfigurationService($this->configurationManager, $this->activeConfiguration);
    }

    /**
     * @test
     */
    public function localIsActiveReturnsTrueIfValuesMatch()
    {
        $this->assertTrue($this->subject->localIsActive('main/bla'));
    }

    /**
     * @test
     */
    public function localIsActiveReturnsFalseIfValuesDoNotMatch()
    {
        $this->assertFalse($this->subject->localIsActive('main/foo'));
        $this->assertFalse($this->subject->localIsActive('main/bazz'));
        $this->assertFalse($this->subject->localIsActive('main/baz'));
    }

    /**
     * @test
     */
    public function getActiveWillReturnActiveConfiguration()
    {
        $this->assertSame(
            [
                'default' => 'value',
                'bla' => 'blupp',
                'bazz' => 'buh',
                'foo' => 'baz'
            ],
            $this->subject->getLocal('main')
        );
    }

    /**
     * @test
     */
    public function getLocalWillReturnMergedDefaultConfiguration()
    {
        $this->assertSame(
            [
                'default' => 'value',
                'bla' => 'blupp',
                'baz' => 'bah',
                'foo' => 'bar'
            ],
            $this->subject->getActive('main')
        );
    }

    /**
     * @test
     */
    public function hasLocalChecksAgainstMergedDefaultConfiguration()
    {
        $this->assertTrue($this->subject->hasLocal('main/default'));
    }
}
