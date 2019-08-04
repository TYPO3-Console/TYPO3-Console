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
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\ChattyUpgradeWizard;
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\DummyUpgradeWizard;
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\RepeatableUpgradeWizard;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;

class UpgradeWizardExecutorTest extends UnitTestCase
{
    protected function setUp()
    {
        if (!interface_exists(ChattyInterface::class)) {
            // @deprecated will be removed with 6.0
            $this->markTestSkipped('Skipping new upgrade tests on TYPO3 8.7');
        }
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * @test
     */
    public function wizardIsNotCalledWhenDone()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->updateNecessary()->willReturn(false);

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function wizardIsCalledWhenNotDoneAndMarkedExecuted()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->updateNecessary()->willReturn(true);
        $upgradeWizardProphecy->executeUpdate()->shouldBeCalled()->willReturn(true);
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $registryProphecy = $this->prophesize(Registry::class);
        $registryProphecy->set('installUpdate', get_class($upgradeWizardProphet), 1)->shouldBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $registryProphecy->reveal());
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
        $upgradeWizardProphecy->updateNecessary()->willReturn(true);
        $upgradeWizardProphecy->executeUpdate()->shouldBeCalled()->willReturn(false);
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $registryProphecy = $this->prophesize(Registry::class);
        $registryProphecy->set('installUpdate', get_class($upgradeWizardProphet), 1)->shouldBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $registryProphecy->reveal());
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
        $upgradeWizardProphecy->updateNecessary()->willReturn(true);
        $upgradeWizardProphecy->executeUpdate()->shouldBeCalled()->willReturn(false);
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $registryProphecy = $this->prophesize(Registry::class);
        $registryProphecy->set('installUpdate', get_class($upgradeWizardProphet), 0)->shouldBeCalled();
        $registryProphecy->set('installUpdate', get_class($upgradeWizardProphet), 1)->shouldBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $registryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test', [], true);
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function repeatableWizardsAreNotMarkedDoneAfterExecution()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(RepeatableUpgradeWizard::class);
        $upgradeWizardProphecy->updateNecessary()->willReturn(true);
        $upgradeWizardProphecy->executeUpdate()->shouldBeCalled()->willReturn(true);
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $registryProphecy = $this->prophesize(Registry::class);
        $registryProphecy->set('installUpdate', get_class($upgradeWizardProphet), 1)->shouldNotBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $registryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertTrue($result->hasPerformed());
    }

    /**
     * @test
     */
    public function updateNecessaryOutputWillBeCapturedForChattyWizard()
    {
        $registryProphecy = $this->prophesize(Registry::class);
        $registryProphecy->set('installUpdate', ChattyUpgradeWizard::class, 1)->shouldBeCalled();

        $upgradeWizard = new ChattyUpgradeWizard();
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $factoryProphecy->create(ChattyUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $registryProphecy->reveal());
        $result = $subject->executeWizard(ChattyUpgradeWizard::class);
        $this->assertTrue($result->hasPerformed());
        $this->assertSame('updateNecessaryexecuteUpdate', $result->getMessages()[0] ?? '');
    }

    /**
     * @test
     */
    public function updateNecessaryOutputWillBeCapturedForChattyWizardEvenIfWizardIsNotPerformed()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ChattyUpgradeWizard(false);

        $factoryProphecy->create(ChattyUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard(ChattyUpgradeWizard::class);
        $this->assertFalse($result->hasPerformed());
        $this->assertSame('updateNecessary', $result->getMessages()[0] ?? '');
    }
}
