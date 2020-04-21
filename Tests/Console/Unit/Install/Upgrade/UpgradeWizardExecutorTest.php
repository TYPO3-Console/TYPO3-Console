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
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\ConfirmableUpgradeWizard;
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\DummyUpgradeWizard;
use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\RepeatableUpgradeWizard;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

class UpgradeWizardExecutorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function wizardIsNotCalledWhenDone()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->willReturn(false)->shouldBeCalled();
        $upgradeWizardProphecy->executeUpdate()->shouldNotBeCalled();

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldBeCalled();

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertFalse($result->hasPerformed());
        $this->assertContains('Upgrade wizard "FOO" was skipped because no operation is needed', implode(chr(10), $result->getMessages()));
    }

    /**
     * @test
     */
    public function wizardIsNotCalledWhenMarkedExecuted()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->shouldNotBeCalled();
        $upgradeWizardProphecy->executeUpdate()->shouldNotBeCalled();

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(true)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldNotBeCalled();

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertFalse($result->hasPerformed());
        $this->assertContains('Upgrade wizard "FOO" was skipped because it is marked as done.', implode(chr(10), $result->getMessages()));
    }

    /**
     * @test
     */
    public function wizardIsCalledWhenNotDoneAndNotMarkedExecuted()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphecy->executeUpdate()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertTrue($result->hasPerformed());
    }

    /**
     * @test
     */
    public function wizardIsCalledButNotMarkedAsExecutedWhenFailed()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphecy->executeUpdate()->willReturn(false)->shouldBeCalled();
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldNotBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertTrue($result->hasPerformed());
        $this->assertTrue($result->hasErrored());
    }

    /**
     * @test
     */
    public function wizardIsCalledButNotMarkedAsExecutedWhenFailedEvenWithPassedArguments()
    {
        $upgradeWizard = new ConfirmableUpgradeWizard(true, false, false);
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $factoryProphecy->create(ConfirmableUpgradeWizard::class)->willReturn($upgradeWizard);

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ConfirmableUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ConfirmableUpgradeWizard::class)->shouldNotBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ConfirmableUpgradeWizard::class, ['confirm' => true]);
        $this->assertTrue($result->hasPerformed());
        $this->assertTrue($result->hasErrored());
    }

    /**
     * @test
     */
    public function wizardIsDoneButCalledWhenForced()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphecy->executeUpdate()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(true)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldNotBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test', [], true);
        $this->assertTrue($result->hasPerformed());
        $this->assertContains('Upgrade wizard "FOO" was executed (forced)', implode(chr(10), $result->getMessages()));
    }

    /**
     * @test
     */
    public function wizardIsNotDoneCalledWithForcedAndMarkedDone()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphecy->executeUpdate()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test', [], true);
        $this->assertTrue($result->hasPerformed());
    }

    /**
     * @test
     */
    public function repeatableWizardsAreNotMarkedDoneAfterExecution()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(RepeatableUpgradeWizard::class);
        $upgradeWizardProphecy->getIdentifier()->willReturn('FOO')->shouldBeCalled();
        $upgradeWizardProphecy->updateNecessary()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphecy->executeUpdate()->willReturn(true)->shouldBeCalled();
        $upgradeWizardProphet = $upgradeWizardProphecy->reveal();
        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphet);

        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone('FOO')->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone('FOO')->shouldNotBeCalled();

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test');
        $this->assertTrue($result->hasPerformed());
    }

    /**
     * @test
     */
    public function updateNecessaryOutputWillBeCapturedForChattyWizard()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ChattyUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ChattyUpgradeWizard::class)->shouldBeCalled();

        $upgradeWizard = new ChattyUpgradeWizard();
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $factoryProphecy->create(ChattyUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ChattyUpgradeWizard::class);
        $this->assertTrue($result->hasPerformed());
        $this->assertSame('updateNecessaryexecuteUpdate', $result->getMessages()[0] ?? '');
    }

    /**
     * @test
     */
    public function updateNecessaryOutputWillBeCapturedForChattyWizardEvenIfWizardIsNotPerformed()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ChattyUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ChattyUpgradeWizard::class)->shouldBeCalled();

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ChattyUpgradeWizard(false);

        $factoryProphecy->create(ChattyUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ChattyUpgradeWizard::class);
        $this->assertFalse($result->hasPerformed());
        $this->assertSame('updateNecessary', $result->getMessages()[0] ?? '');
    }

    /**
     * @test
     */
    public function userDecidesToExecuteDoesExecuteWizard()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ConfirmableUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ConfirmableUpgradeWizard::class)->shouldBeCalled();

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ConfirmableUpgradeWizard(true, true);

        $factoryProphecy->create(ConfirmableUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ConfirmableUpgradeWizard::class, ['confirm' => true]);
        $this->assertTrue($result->hasPerformed());
    }

    /**
     * @test
     */
    public function userDecidesToNotExecuteAndNoDecisionRequiredDoesNotExecuteWizardButMarksItExecuted()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ConfirmableUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ConfirmableUpgradeWizard::class)->shouldBeCalled();

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ConfirmableUpgradeWizard(true, true, false);

        $factoryProphecy->create(ConfirmableUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ConfirmableUpgradeWizard::class, ['confirm' => false]);
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function userDecidesToNotExecuteButDecisionRequiredDoesNotExecuteWizard()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ConfirmableUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ConfirmableUpgradeWizard::class)->shouldNotBeCalled();

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ConfirmableUpgradeWizard(true, true, true);

        $factoryProphecy->create(ConfirmableUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ConfirmableUpgradeWizard::class, ['confirm' => false]);
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function userUndecidedAndDecisionNeeded()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ConfirmableUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ConfirmableUpgradeWizard::class)->shouldNotBeCalled();

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ConfirmableUpgradeWizard(true, true, true);

        $factoryProphecy->create(ConfirmableUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ConfirmableUpgradeWizard::class);
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @test
     */
    public function userUndecidedAndDecisionNotNeeded()
    {
        $upgradeWizardServiceProphecy = $this->prophesize(UpgradeWizardsService::class);
        $upgradeWizardServiceProphecy->isWizardDone(ConfirmableUpgradeWizard::class)->willReturn(false)->shouldBeCalled();
        $upgradeWizardServiceProphecy->markWizardAsDone(ConfirmableUpgradeWizard::class)->shouldNotBeCalled();

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ConfirmableUpgradeWizard(true, true, false);

        $factoryProphecy->create(ConfirmableUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal(), $upgradeWizardServiceProphecy->reveal());
        $result = $subject->executeWizard(ConfirmableUpgradeWizard::class);
        $this->assertFalse($result->hasPerformed());
    }
}
