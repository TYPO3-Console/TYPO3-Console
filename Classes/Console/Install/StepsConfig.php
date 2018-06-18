<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install;

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

use Helhum\ConfigLoader\ConfigurationReaderFactory;
use Helhum\ConfigLoader\Processor\PlaceholderValue;

class StepsConfig
{
    /**
     * @var string
     */
    private $baseConfigFile;

    public function __construct(string $baseConfigFile = null)
    {
        $this->baseConfigFile = $baseConfigFile ?: __DIR__ . '/../../../Configuration/Install/InstallSteps.yaml';
    }

    public function getInstallSteps(string $stepsConfigFile = null): array
    {
        $stepsConfigFile = $stepsConfigFile ?: (string)getenv('TYPO3_INSTALL_SETUP_STEPS') ?: $this->baseConfigFile;
        $stepsConfigFile = (string)realpath($stepsConfigFile);

        return (new PlaceholderValue(false))->processConfig(
            $this->createConfigFactory($stepsConfigFile)
                 ->createRootReader($stepsConfigFile)
                 ->readConfig()
        );
    }

    private function createConfigFactory(string $stepsConfigFile): ConfigurationReaderFactory
    {
        $factory = new ConfigurationReaderFactory(dirname($stepsConfigFile));
        $factory->setReaderFactoryForType(
            'console',
            function () use ($factory) {
                return $factory->createRootReader($this->baseConfigFile);
            },
            false
        );

        return $factory;
    }
}
