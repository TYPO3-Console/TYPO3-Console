<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Install\Upgrade;

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

use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardFactory;
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\LegacyUpgradeWizard;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

class UpgradeWizardFactoryTest extends UnitTestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('TODO: needs a complete rewrite');
    }

    /**
     * @test
     */
    public function createsWizardFromRegistry()
    {
        $registryFixture = [
            'id' => 'Foo\\Test',
        ];

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $upgradeWizardProphecy = $this->prophesize(AbstractUpdate::class);

        $objectManagerProphecy->get('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());
        $upgradeWizardProphecy->setIdentifier('id');

        $subject = new UpgradeWizardFactory($objectManagerProphecy->reveal(), $registryFixture);
        $this->assertSame($upgradeWizardProphecy->reveal(), $subject->create('id'));
    }

    /**
     * @test
     */
    public function createsWizardWithClassName()
    {
        $registryFixture = [];

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $upgradeWizardProphecy = $this->prophesize(LegacyUpgradeWizard::class);

        $objectManagerProphecy->get(LegacyUpgradeWizard::class)->willReturn($upgradeWizardProphecy->reveal());
        $upgradeWizardProphecy->setIdentifier(LegacyUpgradeWizard::class);

        $subject = new UpgradeWizardFactory($objectManagerProphecy->reveal(), $registryFixture);
        $this->assertSame($upgradeWizardProphecy->reveal(), $subject->create(LegacyUpgradeWizard::class));
    }

    /**
     * @test
     */
    public function createsCoreWizardFromRegistry()
    {
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
        ];

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $upgradeWizardProphecy = $this->prophesize(AbstractUpdate::class);

        $objectManagerProphecy->get('TYPO3\\CMS\\Install\\Updates\\FooUpgrade')->willReturn($upgradeWizardProphecy->reveal());
        $upgradeWizardProphecy->setIdentifier('TYPO3\\CMS\\Install\\Updates\\FooUpgrade');

        $subject = new UpgradeWizardFactory($objectManagerProphecy->reveal(), $registryFixture);
        $this->assertSame($upgradeWizardProphecy->reveal(), $subject->create('FooUpgrade'));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionCode 1491914890
     * @test
     */
    public function throwsExceptionForInvalidIdentifier()
    {
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
        ];
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);

        $subject = new UpgradeWizardFactory($objectManagerProphecy->reveal(), $registryFixture);
        $subject->create('foo');
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionCode 1508495588
     * @test
     */
    public function throwsExceptionForInvalidIdentifierWhenFetchingShortIdentifier()
    {
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
        ];
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);

        $subject = new UpgradeWizardFactory($objectManagerProphecy->reveal(), $registryFixture);
        $subject->getShortIdentifier('foo');
    }

    public function shortIdentifierCanBeDeterminedDataProvider()
    {
        return [
            'Core class name' => [
                'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
                'FooUpgrade',
            ],
            'Core short identifier' => [
                'FooUpgrade',
                'FooUpgrade',
            ],
            'Other class name' => [
                'Helhum\\Install\\Updates\\BarUpgrade',
                'bar',
            ],
            'Other class name not shortened' => [
                'Helhum\\Install\\Updates\\FooUpgrade',
                'Helhum\\Install\\Updates\\FooUpgrade',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider shortIdentifierCanBeDeterminedDataProvider
     * @param string $identifierOrClassName
     * @param string $expectedIdentifier
     */
    public function shortIdentifierCanBeDetermined($identifierOrClassName, $expectedIdentifier)
    {
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
            'bar' => 'Helhum\\Install\\Updates\\BarUpgrade',
            'Helhum\\Install\\Updates\\FooUpgrade' => 'Helhum\\Install\\Updates\\FooUpgrade',
        ];
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);

        $subject = new UpgradeWizardFactory($objectManagerProphecy->reveal(), $registryFixture);
        $this->assertSame($expectedIdentifier, $subject->getShortIdentifier($identifierOrClassName));
    }
}
