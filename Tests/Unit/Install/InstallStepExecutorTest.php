<?php
namespace Helhum\Typo3Console\Tests\Unit\Install;

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

use Helhum\Typo3Console\Install\InstallStepActionExecutor;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Install\Controller\Action\Step\AbstractStepAction;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;

class InstallStepExecutorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function needsExecutionThrowsRedirectIsDetected()
    {
        $actionMock = $this->getMockBuilder(AbstractStepAction::class)->disableOriginalConstructor()->getMock();
        $actionMock->expects($this->any())
            ->method('needsExecution')
            ->willReturnCallback(function () {
                throw new RedirectException();
            });
        $objectManagerMock = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturn($actionMock);
        $executor = new InstallStepActionExecutor($objectManagerMock);
        $response = $executor->executeActionWithArguments('test', []);
        $this->assertTrue($response->actionNeedsReevaluation());
    }

    /**
     * @test
     */
    public function actionIsNeverExecutedIfNotNeeded()
    {
        $actionMock = $this->getMockBuilder(AbstractStepAction::class)->disableOriginalConstructor()->getMock();
        $actionMock->expects($this->once())
            ->method('needsExecution')
            ->willReturn(false);
        $actionMock->expects($this->never())
            ->method('execute');
        $objectManagerMock = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturn($actionMock);
        $executor = new InstallStepActionExecutor($objectManagerMock);
        $response = $executor->executeActionWithArguments('test', []);
        $this->assertFalse($response->actionNeedsExecution());
    }

    /**
     * @test
     */
    public function actionIsNeverExecutedIfDryRun()
    {
        $actionMock = $this->getMockBuilder(AbstractStepAction::class)->disableOriginalConstructor()->getMock();
        $actionMock->expects($this->once())
            ->method('needsExecution')
            ->willReturn(true);
        $actionMock->expects($this->never())
            ->method('execute');
        $objectManagerMock = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturn($actionMock);
        $executor = new InstallStepActionExecutor($objectManagerMock);
        $response = $executor->executeActionWithArguments('test', [], true);
        $this->assertTrue($response->actionNeedsExecution());
    }

    /**
     * @test
     */
    public function actionIsExecutedIfNeeded()
    {
        $actionMock = $this->getMockBuilder(AbstractStepAction::class)->disableOriginalConstructor()->getMock();
        $actionMock->expects($this->once())
            ->method('needsExecution')
            ->willReturn(true);
        $actionMock->expects($this->once())
            ->method('execute')
            ->willReturn([]);
        $objectManagerMock = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturn($actionMock);
        $executor = new InstallStepActionExecutor($objectManagerMock);
        $response = $executor->executeActionWithArguments('test', []);
        $this->assertFalse($response->actionNeedsExecution());
    }
}
