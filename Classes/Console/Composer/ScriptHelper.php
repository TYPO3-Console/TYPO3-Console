<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Composer;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Composer\Script\Event as ScriptEvent;
use Helhum\Typo3Console\Exception;

/**
 * Helper for usage in composer.json script section
 */
class ScriptHelper
{
    /**
     * @param ScriptEvent $event
     * @internal
     * @throws Exception
     */
    public static function setVersion(ScriptEvent $event)
    {
        $version = $event->getArguments()[0];
        if (!preg_match('/\d+\.\d+\.\d+/', $version)) {
            throw new Exception('No valid version number provided!', 1468672604);
        }
        $docConfigFile = __DIR__ . '/../../../Documentation/Settings.yml';
        $content = file_get_contents($docConfigFile);
        $content = preg_replace('/(version|release): \d+\.\d+\.\d+/', '$1: ' . $version, $content);
        file_put_contents($docConfigFile, $content);

        $docConfigFile = __DIR__ . '/../../../Documentation/Settings.cfg';
        $content = file_get_contents($docConfigFile);
        $content = preg_replace('/(version|release) = \d+\.\d+\.\d+/', '$1 = ' . $version, $content);
        file_put_contents($docConfigFile, $content);

        $extEmConfFile = __DIR__ . '/../../../Resources/Private/ExtensionArtifacts/ext_emconf.php';
        $content = file_get_contents($extEmConfFile);
        $content = preg_replace('/(\'version\' => )\'\d+\.\d+\.\d+/', '$1\'' . $version, $content);
        file_put_contents($extEmConfFile, $content);

        $applicationFile = __DIR__ . '/../Mvc/Cli/Symfony/Application.php';
        $content = file_get_contents($applicationFile);
        $content = preg_replace('/(const TYPO3_CONSOLE_VERSION = \')\d+\.\d+\.\d+/', 'const TYPO3_CONSOLE_VERSION = \'' . $version, $content);
        file_put_contents($applicationFile, $content);

        $travisYmlFile = __DIR__ . '/../../../.travis.yml';
        $content = file_get_contents($travisYmlFile);
        $content = preg_replace('/(export COMPOSER_ROOT_VERSION)=\d+\.\d+\.\d+/', '$1=' . $version, $content);
        file_put_contents($travisYmlFile, $content);

        $sonarConfigFile = __DIR__ . '/../../../sonar-project.properties';
        $content = file_get_contents($sonarConfigFile);
        $content = preg_replace('/(sonar.projectVersion)=\d+\.\d+\.\d+/', '$1=' . $version, $content);
        file_put_contents($sonarConfigFile, $content);
    }

    public static function verifyComposerJsonOfExtension()
    {
        $main = json_decode(file_get_contents('composer.json'), true);
        $extension = json_decode(file_get_contents('Resources/Private/ExtensionArtifacts/composer.json'), true);
        foreach (['description', 'keywords', 'support', 'homepage', 'authors', 'license'] as $name) {
            if ($main[$name] !== $extension[$name]) {
                throw new Exception(sprintf('Property "%s" is not the same', $name));
            }
        }
        if ($main['require']['typo3/cms-core'] !== $extension['require']['typo3/cms-core']) {
            throw new Exception('Extension core version does not match main core version');
        }
    }
}
