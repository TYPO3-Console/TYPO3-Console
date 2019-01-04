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

use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;

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
    protected $priority = 15;

    /**
     * Convert from $source to $targetType, a noop if the source is an array.
     * If it is a string it will be exploded by the configured string delimiter.
     *
     * @param string|array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @throws InvalidPropertyMappingConfigurationException
     * @return array
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): array
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
     * @param PropertyMappingConfigurationInterface $configuration
     * @throws InvalidPropertyMappingConfigurationException
     * @return string
     */
    private function getConfiguredStringDelimiter(PropertyMappingConfigurationInterface $configuration = null): string
    {
        if ($configuration === null) {
            return self::DEFAULT_STRING_DELIMITER;
        }
        $stringDelimiter = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_STRING_DELIMITER);
        if ($stringDelimiter === null) {
            return self::DEFAULT_STRING_DELIMITER;
        }
        if (!is_string($stringDelimiter)) {
            throw new InvalidPropertyMappingConfigurationException('CONFIGURATION_STRING_DELIMITER must be of type string, "' . (is_object($stringDelimiter) ? get_class($stringDelimiter) : gettype($stringDelimiter)) . '" given', 1368433339);
        }

        return $stringDelimiter;
    }
}
