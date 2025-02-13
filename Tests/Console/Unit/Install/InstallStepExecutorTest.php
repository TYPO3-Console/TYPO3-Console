<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Install;

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

use Helhum\Typo3Console\Install\InstallStepActionExecutor;
use Helhum\Typo3Console\Install\Upgrade\SilentConfigurationUpgrade;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Install\Controller\InstallerController;

class InstallStepExecutorTest extends TestCase
{
    /**
     * @test
     */
    public function actionIsNeverExecutedIfNotNeeded()
    {
        $silentConfigUpgradeMock = $this->getMockBuilder(SilentConfigurationUpgrade::class)->disableOriginalConstructor()->getMock();
        $installerControllerMock = $this->createMock(InstallerController::class);
        $installerControllerMock->method('checkEnvironmentAndFoldersAction')->willReturn(new JsonResponse(['success' => true]));
        $executor = new InstallStepActionExecutor($silentConfigUpgradeMock, $installerControllerMock);
        $response = $executor->executeActionWithArguments('environmentAndFolders');
        $this->assertFalse($response->actionNeedsExecution());
    }

    /**
     * @test
     */
    public function actionIsNeverExecutedIfDryRun()
    {
        $silentConfigUpgradeMock = $this->getMockBuilder(SilentConfigurationUpgrade::class)->disableOriginalConstructor()->getMock();
        $installerControllerMock = $this->createMock(InstallerController::class);
        $installerControllerMock->method('checkEnvironmentAndFoldersAction')->willReturn(new JsonResponse(['success' => false]));
        $executor = new InstallStepActionExecutor($silentConfigUpgradeMock, $installerControllerMock);
        $response = $executor->executeActionWithArguments('environmentAndFolders', [], true);
        $this->assertTrue($response->actionNeedsExecution());
    }

    /**
     * @test
     */
    public function actionIsExecutedIfNeeded()
    {
        $request = (new ServerRequest())->withParsedBody(
            [
                'install' => [
                    'values' => [],
                ],
            ]
        );
        $requestFactory = function () use ($request) {
            return $request;
        };
        $silentConfigUpgradeMock = $this->getMockBuilder(SilentConfigurationUpgrade::class)->disableOriginalConstructor()->getMock();
        $installerControllerMock = $this->createMock(InstallerController::class);
        $installerControllerMock->method('checkEnvironmentAndFoldersAction')->willReturn(new JsonResponse(['success' => false]));
        $installerControllerMock->method('executeEnvironmentAndFoldersAction')->with($request)->willReturn(new JsonResponse(['success' => true]));
        $executor = new InstallStepActionExecutor($silentConfigUpgradeMock, $installerControllerMock, $requestFactory);
        $response = $executor->executeActionWithArguments('environmentAndFolders');
        $this->assertFalse($response->actionNeedsExecution());
    }
}
