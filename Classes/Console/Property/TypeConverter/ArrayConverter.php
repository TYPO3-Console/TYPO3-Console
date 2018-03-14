<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Property\TypeConverter;

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

/**
 * Converter which transforms arrays to arrays.
 *
 * @api
 */
class ArrayConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter
{
    /**
     * @var string
     */
    const CONFIGURATION_STRING_DELIMITER = 'stringDelimiter';

    /**
     * @var string
     */
    const DEFAULT_STRING_DELIMITER = ',';

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array', 'string'];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var int
     */
    protected $priority = 2;

    /**
     * Convert from $source to $targetType, a noop if the source is an array.
     * If it is a string it will be exploded by the configured string delimiter.
     *
     * @param string|array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return array
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_string($source)) {
            if ($source === '') {
                return [];
            }

            return array_filter(array_map('trim', explode($this->getConfiguredStringDelimiter($configuration), $source)));
        }

        return $source;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     * @return string
     */
    protected function getConfiguredStringDelimiter(\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_STRING_DELIMITER;
        }
        $stringDelimiter = $configuration->getConfigurationValue(\Helhum\Typo3Console\Property\TypeConverter\ArrayConverter::class, self::CONFIGURATION_STRING_DELIMITER);
        if ($stringDelimiter === null) {
            return self::DEFAULT_STRING_DELIMITER;
        } elseif (!is_string($stringDelimiter)) {
            throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_STRING_DELIMITER must be of type string, "' . (is_object($stringDelimiter) ? get_class($stringDelimiter) : gettype($stringDelimiter)) . '" given', 1368433339);
        }

        return $stringDelimiter;
    }
}
