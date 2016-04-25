<?php
namespace Helhum\Typo3Console\Service\Configuration;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class ConfigurationService
 */
class ConfigurationService implements SingletonInterface
{
    const EXCEPTION_CODE_ARRAY_KEY_NOT_FOUND = 1341397869;

    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    private $activeConfiguration;

    /**
     * ConfigurationService constructor.
     *
     * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
     * @param array $activeConfiguration
     */
    public function __construct(\TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager, array $activeConfiguration = array())
    {
        $this->configurationManager = $configurationManager;
        $this->activeConfiguration = $activeConfiguration;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function hasLocal($path)
    {
        return $this->has($path, $this->getMergedConfiguration());
    }

    /**
     * @param string $path
     * @return bool
     */
    public function hasActive($path)
    {
        return $this->has($path, $this->activeConfiguration);
    }

    /**
     * @param string $path
     * @return mixed
     * @throws ConfigurationValueNotFoundException
     */
    public function getLocal($path)
    {
        return $this->get($path, $this->getMergedConfiguration());
    }

    /**
     * @param string $path
     * @return mixed
     * @throws ConfigurationValueNotFoundException
     */
    public function getActive($path)
    {
        return $this->get($path, $this->activeConfiguration);
    }

    /**
     * @param string $path
     * @param array $config
     * @return bool
     */
    protected function has($path, array $config)
    {
        try {
            ArrayUtility::getValueByPath($config, $path);
        } catch (\RuntimeException $e) {
            if ($e->getCode() !== self::EXCEPTION_CODE_ARRAY_KEY_NOT_FOUND) {
                throw $e;
            }
            return false;
        }
        return true;
    }

    /**
     * @param string $path
     * @param array $config
     * @return mixed
     * @throws ConfigurationValueNotFoundException
     */
    protected function get($path, array $config)
    {
        try {
            $value = ArrayUtility::getValueByPath($config, $path);
        } catch (\RuntimeException $e) {
            if ($e->getCode() !== self::EXCEPTION_CODE_ARRAY_KEY_NOT_FOUND) {
                throw $e;
            }
            throw new ConfigurationValueNotFoundException('No value found for this key!', 1461607530);
        }
        return $value;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function removeLocal($path)
    {
        return $this->configurationManager->removeLocalConfigurationKeysByPath(array($path));
    }

    /**
     * @param string $path
     * @param mixed $value
     * @return bool
     */
    public function setLocal($path, $value)
    {
        if (
            !$this->localIsActive($path)
            || !$this->hasLocal($path)
        ) {
            return false;
        }
        $this->configurationManager->setLocalConfigurationValueByPath($path, $value);
        return true;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function localIsActive($path)
    {
        if ($this->hasLocal($path)) {
            return $this->hasActive($path) && $this->getLocal($path) === $this->getActive($path);
        }
        return !$this->hasActive($path);
    }

    /**
     * @return array
     */
    protected function getMergedConfiguration()
    {
        $mergedConfig = $this->configurationManager->getDefaultConfiguration();
        ArrayUtility::mergeRecursiveWithOverrule($mergedConfig, $this->configurationManager->getLocalConfiguration());
        return $mergedConfig;
    }
}
