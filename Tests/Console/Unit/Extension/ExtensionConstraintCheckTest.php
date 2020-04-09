<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Extension;

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

use Helhum\Typo3Console\Extension\ExtensionConstraintCheck;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;

class ExtensionConstraintCheckTest extends UnitTestCase
{
    public function constraintsDataProvider()
    {
        return [
            'matching constraint' => [
                '6.2.0 - 7.6.99',
                '7.6.0',
                '',
            ],
            'matching constraint no upper' => [
                '6.2.0 - ',
                '7.6.0',
                '',
            ],
            'matching constraint no lower' => [
                '- 7.6.99',
                '4.5.0',
                '',
            ],
            'matching constraint no value' => [
                '',
                '4.5.0',
                '',
            ],
            'failing lower constraint' => [
                '6.2.0 - 7.6.99',
                '4.5.0',
                '"dummy" requires TYPO3 versions 6.2.0 - 7.6.99. It is not compatible with TYPO3 version "4.5.0"',
            ],
            'failing higher constraint' => [
                '6.2.0 - 7.6.99',
                '8.7.0',
                '"dummy" requires TYPO3 versions 6.2.0 - 7.6.99. It is not compatible with TYPO3 version "8.7.0"',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider constraintsDataProvider
     * @param string $constraint
     * @param string $typo3Version
     * @param string $expectedResult
     */
    public function matchConstraintsReturnsCorrectResults($constraint, $typo3Version, $expectedResult)
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('dummy');
        $packageProphecy->getPackagePath()->willReturn(PATH_site . 'typo3conf/ext/dummy/');

        $emConfUtilityProphecy = $this->prophesize(EmConfUtility::class);
        $emConfUtilityProphecy->includeEmConf(
            'dummy',
            [
                'key' => 'dummy',
                'packagePath' => PATH_site . 'typo3conf/ext/dummy/',
            ]
        )->willReturn(
            [
                'constraints' => [
                    'depends' => [
                        'typo3' => $constraint,
                    ],
                ],
            ]
        );
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $subject = new ExtensionConstraintCheck(
            $emConfUtilityProphecy->reveal(),
            $packageManagerProphecy->reveal()
        );

        $this->assertSame($expectedResult, $subject->matchConstraints($packageProphecy->reveal(), $typo3Version));
    }

    /**
     * @test
     * @dataProvider constraintsDataProvider
     * @param string $constraint
     * @param string $typo3Version
     */
    public function matchAllConstraintsIgnoresExtensionsNotInExtFolder($constraint, $typo3Version)
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('dummy');
        $packageProphecy->getPackagePath()->willReturn(PATH_site . 'typo3/sysext/dummy/');

        $emConfUtilityProphecy = $this->prophesize(EmConfUtility::class);
        $emConfUtilityProphecy->includeEmConf(
            'dummy',
            [
                'key' => 'dummy',
                'siteRelPath' => 'typo3conf/ext/dummy/',
            ]
        )->willReturn(
            [
                'constraints' => [
                    'depends' => [
                        'typo3' => $constraint,
                    ],
                ],
            ]
        );
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $subject = new ExtensionConstraintCheck(
            $emConfUtilityProphecy->reveal(),
            $packageManagerProphecy->reveal()
        );

        $this->assertSame([], $subject->matchAllConstraints([$packageProphecy->reveal()], $typo3Version));
    }
}
