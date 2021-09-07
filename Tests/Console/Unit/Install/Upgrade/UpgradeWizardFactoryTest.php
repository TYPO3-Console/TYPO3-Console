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
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\DummyUpgradeWizard;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class UpgradeWizardFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createsWizardFromRegistry(): void
    {
        $registryFixture = [
            'id' => 'Foo\\Test',
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $upgradeWizardProphecy = $this->prophesize(UpgradeWizardInterface::class);

        $containerProphecy->get('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardFactory($containerProphecy->reveal(), $registryFixture);
        self::assertSame($upgradeWizardProphecy->reveal(), $subject->create('id'));
    }

    /**
     * @test
     */
    public function createsWizardWithClassName(): void
    {
        $registryFixture = [];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);

        $containerProphecy->get(DummyUpgradeWizard::class)->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardFactory($containerProphecy->reveal(), $registryFixture);
        self::assertSame($upgradeWizardProphecy->reveal(), $subject->create(DummyUpgradeWizard::class));
    }

    /**
     * @test
     */
    public function createsCoreWizardFromRegistry(): void
    {
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
        ];

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $upgradeWizardProphecy = $this->prophesize(UpgradeWizardInterface::class);

        $containerProphecy->get('TYPO3\\CMS\\Install\\Updates\\FooUpgrade')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardFactory($containerProphecy->reveal(), $registryFixture);
        self::assertSame($upgradeWizardProphecy->reveal(), $subject->create('FooUpgrade'));
    }

    /**
     * @test
     */
    public function throwsExceptionForInvalidIdentifier(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1491914890);
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
        ];
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $subject = new UpgradeWizardFactory($containerProphecy->reveal(), $registryFixture);
        $subject->create('foo');
    }

    /**
     * @test
     */
    public function throwsExceptionForInvalidIdentifierWhenFetchingShortIdentifier(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1508495588);
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
        ];
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $subject = new UpgradeWizardFactory($containerProphecy->reveal(), $registryFixture);
        $subject->getShortIdentifier('foo');
    }

    public function shortIdentifierCanBeDeterminedDataProvider(): array
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
    public function shortIdentifierCanBeDetermined($identifierOrClassName, $expectedIdentifier): void
    {
        $registryFixture = [
            'TYPO3\\CMS\\Install\\Updates\\FooUpgrade' => 'TYPO3\\CMS\\Install\\Updates\\FooUpgrade',
            'bar' => 'Helhum\\Install\\Updates\\BarUpgrade',
            'Helhum\\Install\\Updates\\FooUpgrade' => 'Helhum\\Install\\Updates\\FooUpgrade',
        ];
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $subject = new UpgradeWizardFactory($containerProphecy->reveal(), $registryFixture);
        self::assertSame($expectedIdentifier, $subject->getShortIdentifier($identifierOrClassName));
    }
}
