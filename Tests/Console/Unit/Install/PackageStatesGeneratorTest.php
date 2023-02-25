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

use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3Console\Package\UncachedPackageManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Package\PackageInterface;

class PackageStatesGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function localExtensionsAreMarkedActive()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate();
    }

    /**
     * @test
     */
    public function localExtensionsAreNotMarkedActiveWhenExcluded()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3conf/ext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->deactivatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate([], ['foo'], false);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreNotMarkedAsActiveByDefaultInNonComposerMode()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->deactivatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate();
    }

    /**
     * @test
     */
    public function defaultFrameworkExtensionsAreNotMarkedAsActiveByDefaultInNonComposerMode()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->isPartOfFactoryDefault()->willReturn(true);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->deactivatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate();
    }

    /**
     * @test
     */
    public function defaultFrameworkExtensionsAreMarkedAsActiveWhenSwitchProvidedInNonComposerMode()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->isPartOfFactoryDefault()->willReturn(true);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal(), false);
        $packageStatesGenerator->generate([], [], true);
    }

    /**
     * @test
     */
    public function defaultFrameworkExtensionsAreNotMarkedAsActiveWhenSwitchProvidedButExcludedInNonComposerMode()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->isPartOfFactoryDefault()->willReturn(true);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->deactivatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal(), false);
        $packageStatesGenerator->generate([], ['foo'], true);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreMarkedAsActiveWhenProvided()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate(['foo']);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreMarkedAsActiveWhenProvidedEvenWhenExcluded()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate(['foo'], ['foo'], false);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreMarkedAsActiveWhenProtected()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(true);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate(['foo']);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreMarkedAsActiveWhenProtectedEvenWhenExcluded()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(true);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate(['foo'], ['foo'], false);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreMarkedAsActiveWhenPartOfMinimalUsableSystem()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(true);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate(['foo']);
    }

    /**
     * @test
     */
    public function frameworkExtensionsAreMarkedAsActiveWhenPartOfMinimalUsableSystemEvenWhenExcluded()
    {
        $packageProphecy = $this->prophesize(PackageInterface::class);
        $packageProphecy->getPackageKey()->willReturn('foo');
        $packageProphecy->isProtected()->willReturn(false);
        $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(true);
        $packageProphecy->getPackagePath()->willReturn(getenv('TYPO3_PATH_ROOT') . '/typo3/sysext/foo');

        $packages = [
            $packageProphecy->reveal(),
        ];

        $packageManagerProphecy = $this->prophesize(UncachedPackageManager::class);
        $packageManagerProphecy->scanAvailablePackages()->shouldBeCalled();
        $packageManagerProphecy->forceSortAndSavePackageStates()->shouldBeCalled();

        $packageManagerProphecy->activatePackage('foo')->shouldBeCalled();
        $packageManagerProphecy->getAvailablePackages()->willReturn($packages);
        $packageManagerProphecy->getActivePackages()->shouldBeCalled();
        $packageStatesGenerator = new PackageStatesGenerator($packageManagerProphecy->reveal());
        $packageStatesGenerator->generate(['foo'], ['foo'], false);
    }
}
