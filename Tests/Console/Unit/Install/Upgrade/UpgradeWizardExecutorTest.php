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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;

class UpgradeWizardExecutorTest extends UnitTestCase
{
    private $singletonInstances = [];

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    protected function tearDown()
    {
        parent::tearDown();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
    }

    /**
     * @test
     */
    public function wizardIsNotCalledWhenDone()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $upgradeWizardProphecy->shouldRenderWizard()->willReturn(false);
        if (interface_exists(ChattyInterface::class)) {
            $upgradeWizardProphecy->setOutput(new BufferedOutput())->shouldBeCalled();
        }

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
        $this->assertOutputInitForChattyWizard($upgradeWizardProphecy);
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
    public function updateNecessaryOutputWillBeCapturedForChattyWizard()
    {
        if (!interface_exists(ChattyInterface::class)) {
            $this->markTestSkipped('ChattyInterface not available on TYPO3 8.7');
        }
        $registryProphecy = $this->prophesize(Registry::class);
        $registryProphecy->set('installUpdate', ChattyUpgradeWizard::class, 1)->shouldBeCalled();
        GeneralUtility::setSingletonInstance(Registry::class, $registryProphecy->reveal());

        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ChattyUpgradeWizard();

        $factoryProphecy->create(ChattyUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard(ChattyUpgradeWizard::class);
        $this->assertTrue($result->hasPerformed());
        $this->assertSame('updateNecessaryexecuteUpdate', $result->getMessages()[0] ?? '');
    }

    /**
     * @test
     */
    public function updateNecessaryOutputWillBeCapturedForChattyWizardEvenIfWizardIsNotPerformed()
    {
        if (!interface_exists(ChattyInterface::class)) {
            $this->markTestSkipped('ChattyInterface not available on TYPO3 8.7');
        }
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizard = new ChattyUpgradeWizard(false);

        $factoryProphecy->create(ChattyUpgradeWizard::class)->willReturn($upgradeWizard);

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard(ChattyUpgradeWizard::class);
        $this->assertFalse($result->hasPerformed());
        $this->assertSame('updateNecessary', $result->getMessages()[0] ?? '');
    }

    /**
     * @test
     */
    public function wizardIsCalledWhenNotDoneButCanStillNotPerform()
    {
        $factoryProphecy = $this->prophesize(UpgradeWizardFactory::class);
        $upgradeWizardProphecy = $this->prophesize(DummyUpgradeWizard::class);
        $this->assertOutputInitForChattyWizard($upgradeWizardProphecy);
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
        if (interface_exists(ChattyInterface::class)) {
            $upgradeWizardProphecy->setOutput(new BufferedOutput())->shouldBeCalled();
        }

        $factoryProphecy->create('Foo\\Test')->willReturn($upgradeWizardProphecy->reveal());

        $subject = new UpgradeWizardExecutor($factoryProphecy->reveal());
        $result = $subject->executeWizard('Foo\\Test', [], true);
        $this->assertFalse($result->hasPerformed());
    }

    /**
     * @param DummyUpgradeWizard|ObjectProphecy $upgradeWizardProphecy
     */
    private function assertOutputInitForChattyWizard(ObjectProphecy $upgradeWizardProphecy)
    {
        if (!interface_exists(ChattyInterface::class)) {
            return;
        }

        /** @var OutputInterface $outputInterfaceArgument */
        $outputInterfaceArgument = Argument::type(OutputInterface::class);
        /** @var MethodProphecy $setOutputMethod */
        $setOutputMethod = $upgradeWizardProphecy->setOutput($outputInterfaceArgument);
        $setOutputMethod->shouldBeCalled();
    }
}
