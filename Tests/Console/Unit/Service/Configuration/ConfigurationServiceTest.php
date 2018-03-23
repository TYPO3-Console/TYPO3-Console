<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Service\Configuration;

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

use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
            'baz' => 'bah',
            'foo' => 'bar',
        ],
    ];

    /**
     * @var array
     */
    protected $defaultConfiguration = [
        'default' => [
            'int' => 1,
            'bool' => true,
            'float' => 1.0,
            'string' => '1',
        ],
        'main' => [
            'default' => 'value',
        ],
    ];

    /**
     * @var array
     */
    protected $localConfiguration = [
        'local' => [
            'int' => 1,
            'bool' => true,
            'float' => 1.0,
            'string' => '1',
            'array' => [],
        ],
        'main' => [
            'bla' => 'blupp',
            'bazz' => 'buh',
            'foo' => 'baz',
        ],
    ];

    public function setup()
    {
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)->getMock();
        $this->configurationManager->expects($this->any())
            ->method('getLocalConfiguration')
            ->willReturn($this->localConfiguration);
        $this->configurationManager->expects($this->any())
            ->method('getDefaultConfiguration')
            ->willReturn($this->defaultConfiguration);

        $localConfigurationReference = &$this->localConfiguration;
        $this->configurationManager->expects($this->any())
            ->method('setLocalConfigurationValueByPath')
            ->willReturnCallback(
                function ($path, $value) use (&$localConfigurationReference) {
                    $localConfigurationReference = ArrayUtility::setValueByPath($localConfigurationReference, $path, $value);

                    return true;
                }
            );

        $activeConfiguration = array_replace_recursive($this->defaultConfiguration, $this->localConfiguration, $this->activeConfiguration);
        unset($activeConfiguration['main']['bazz']);
        $this->subject = new ConfigurationService($this->configurationManager, $activeConfiguration);
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
                'foo' => 'bar',
                'baz' => 'bah',
            ],
            $this->subject->getActive('main')
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
                'bazz' => 'buh',
                'foo' => 'baz',
            ],
            $this->subject->getLocal('main')
        );
    }

    /**
     * @test
     */
    public function hasLocalChecksAgainstMergedDefaultConfiguration()
    {
        $this->assertTrue($this->subject->hasLocal('main/default'));
    }

    /**
     * @test
     */
    public function setLocalSetsConfiguration()
    {
        $this->assertTrue($this->subject->setLocal('main/bla', 'ho'));
        $this->assertSame('ho', $this->localConfiguration['main']['bla']);
    }

    /**
     * @test
     */
    public function setLocalSetIntegerConfiguration()
    {
        $this->assertTrue($this->subject->setLocal('default/int', '42'));
        $this->assertSame(42, $this->localConfiguration['default']['int']);
    }

    /**
     * @test
     */
    public function setLocalSetStringConfiguration()
    {
        $this->assertTrue($this->subject->setLocal('default/string', '42'));
        $this->assertSame('42', $this->localConfiguration['default']['string']);
    }

    /**
     * @test
     */
    public function setLocalSetFloatConfiguration()
    {
        $this->assertTrue($this->subject->setLocal('default/float', '3.141592'));
        $this->assertSame(3.141592, $this->localConfiguration['default']['float']);
    }

    /**
     * @test
     */
    public function setLocalSetBooleanConfiguration()
    {
        $this->assertTrue($this->subject->setLocal('default/bool', '0'));
        $this->assertFalse($this->localConfiguration['default']['bool']);
    }

    /**
     * @test
     */
    public function setLocalSetIntegerConfigurationOnlyPresentInLocal()
    {
        $this->assertTrue($this->subject->setLocal('local/int', '42'));
        $this->assertSame(42, $this->localConfiguration['local']['int']);
    }

    /**
     * @test
     */
    public function setLocalSetStringConfigurationOnlyPresentInLocal()
    {
        $this->assertTrue($this->subject->setLocal('local/string', 42));
        $this->assertSame('42', $this->localConfiguration['local']['string']);
    }

    /**
     * @test
     */
    public function setLocalSetFloatConfigurationOnlyPresentInLocal()
    {
        $this->assertTrue($this->subject->setLocal('local/float', '3.141592'));
        $this->assertSame(3.141592, $this->localConfiguration['local']['float']);
    }

    /**
     * @test
     */
    public function setLocalSetBooleanConfigurationOnlyPresentInLocal()
    {
        $this->assertTrue($this->subject->setLocal('local/bool', '0'));
        $this->assertFalse($this->localConfiguration['local']['bool']);
    }

    /**
     * @test
     */
    public function setLocalSetsArrayConfigurationPresentInLocal()
    {
        $this->assertTrue($this->subject->setLocal('local/array', ['foo', 'bar']));
        $this->assertSame(['foo', 'bar'], $this->localConfiguration['local']['array']);
    }

    /**
     * @test
     */
    public function setLocalSetsArrayConfigurationIfNotPresent()
    {
        $this->assertTrue($this->subject->setLocal('local/array2', ['foo', 'bar']));
        $this->assertSame(['foo', 'bar'], $this->localConfiguration['local']['array2']);
    }

    /**
     * @test
     */
    public function setLocalCannotSetConfigurationIfTargetIsNotScalar()
    {
        $this->assertFalse($this->subject->setLocal('local', '0'));
    }
}
