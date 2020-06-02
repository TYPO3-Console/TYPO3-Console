<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Composer\Util;

/**
 * @deprecated Can be removed when Composer 2.0 is made mandatory
 */
class PackageSorter
{
    protected static $isDeprecated = false;

    public static function sortPackages(array $packages): array
    {
        if (!class_exists(\Composer\Util\PackageSorter::class)) {
            self::$isDeprecated = true;

            return $packages;
        }

        return \Composer\Util\PackageSorter::sortPackages($packages);
    }

    public static function isDeprecated(): bool
    {
        return self::$isDeprecated;
    }
}
