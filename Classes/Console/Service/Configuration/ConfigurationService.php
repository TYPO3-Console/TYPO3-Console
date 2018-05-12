<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service\Configuration;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @param ConfigurationManager $configurationManager
     * @param array $activeConfiguration
     */
    public function __construct(ConfigurationManager $configurationManager = null, array $activeConfiguration = [])
    {
        $this->configurationManager = $configurationManager ?: GeneralUtility::makeInstance(ConfigurationManager::class);
        $this->activeConfiguration = $activeConfiguration ?: $GLOBALS['TYPO3_CONF_VARS'];
    }

    /**
     * @param string $path
     * @return bool
     */
    public function hasDefault($path)
    {
        return $this->has($path, $this->configurationManager->getDefaultConfiguration());
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
     * @throws ConfigurationValueNotFoundException
     * @return mixed
     */
    public function getDefault($path)
    {
        return $this->get($path, $this->configurationManager->getDefaultConfiguration());
    }

    /**
     * @param string $path
     * @throws ConfigurationValueNotFoundException
     * @return mixed
     */
    public function getLocal($path)
    {
        return $this->get($path, $this->getMergedConfiguration());
    }

    /**
     * @param string $path
     * @throws ConfigurationValueNotFoundException
     * @return mixed
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
     * @throws ConfigurationValueNotFoundException
     * @return mixed
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
        return $this->configurationManager->removeLocalConfigurationKeysByPath([$path]);
    }

    /**
     * Sets a value in LocalConfiguration.php
     *
     * But only if types are compatible and local config is active
     *
     * @param string $path
     * @param mixed $value
     * @param string $targetType
     * @return bool
     */
    public function setLocal($path, $value, $targetType = '')
    {
        try {
            $value = $this->convertToTargetType($path, $value, $targetType);

            return $this->configurationManager->setLocalConfigurationValueByPath($path, $value);
        } catch (TypesAreNotConvertibleException $e) {
            return false;
        }
    }

    /**
     * Returns true if the value is stored in the LocalConfiguration.php file and
     * is NOT overridden later (e.g. in AdditionalConfiguration.php)
     *
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
     * Convert a value to the type belonging to the given path
     *
     * @param string $path
     * @param string $value
     * @param string $targetType
     * @throws TypesAreNotConvertibleException
     * @return bool|float|int|string
     */
    public function convertToTargetType($path, $value, $targetType = '')
    {
        $targetType = $targetType ?: $this->getType($path);
        $actualType = gettype($value);
        if ($actualType !== $targetType && $targetType !== 'NULL') {
            if ($this->isTypeConvertible($targetType, $actualType)) {
                switch ($targetType) {
                    case 'integer':
                        $value = (int)$value;
                        break;
                    case 'float':
                    case 'double':
                        $value = (float)$value;
                        break;
                    case 'boolean':
                        $value = (bool)$value;
                        break;
                    case 'string':
                        $value = (string)$value;
                        break;
                    default:
                        // We don't know any type conversion, so we better exit
                        throw new TypesAreNotConvertibleException(sprintf('Unknown target type "%s"', $targetType), 1477778705);
                }
            } else {
                // We cannot convert from or to non scalar types, so we better exit
                throw new TypesAreNotConvertibleException(sprintf('Cannot convert type from "%s" to "%s"', $actualType, $targetType), 1477778754);
            }
        }

        return $value;
    }

    /**
     * Returns the type of a value in given config path
     *
     * @param string $path
     * @return string
     */
    private function getType($path)
    {
        $value = null;
        if ($this->hasActive($path)) {
            $value = $this->getActive($path);
        }
        if ($this->hasLocal($path)) {
            $value = $this->getLocal($path);
        }
        if ($this->hasDefault($path)) {
            $value = $this->getDefault($path);
        }

        return gettype($value);
    }

    /**
     * Checks if target and actual type is scalar
     *
     * @param string $targetType
     * @param string $actualType
     * @return bool
     */
    private function isTypeConvertible($targetType, $actualType)
    {
        if (in_array($targetType, ['array', 'object', 'resource'], true)) {
            return false;
        }
        if (in_array($actualType, ['array', 'object', 'resource'], true)) {
            return false;
        }

        return true;
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
