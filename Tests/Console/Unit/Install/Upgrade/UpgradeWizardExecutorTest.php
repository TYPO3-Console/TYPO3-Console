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

use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardExecutor;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardFactory;
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\DummyUpgradeWizard;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class UpgradeWizardExecutorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function wizardIsNotCalledWhenDone()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->shouldRenderWizard()->willReturn(false);

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function wizardIsCalledWhenNotDone()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->shouldRenderWizard()->willReturn(true);
        $upgradeWizardProphecy->performUpdate($queries = [], $message = '')->willReturn(true);

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertTrue($result->hasPerformed());
    }

    /**
     * @test
     */
    public function wizardIsCalledWhenNotDoneButCanStillNotPerform()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->shouldRenderWizard()->willReturn(true);
        $upgradeWizardProphecy->performUpdate($queries = [], $message = '')->willReturn(false);

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function wizardIsDoneButCalledWhenForced()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->shouldRenderWizard()->willReturn(false);
        $upgradeWizardProphecy->markWizardAsDone(0)->shouldBeCalled();
        $upgradeWizardProphecy->performUpdate($queries = [], $message = '')->willReturn(true);

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test', [], true);
        $this->assertFalse($result->hasPerformed());
    }
}
