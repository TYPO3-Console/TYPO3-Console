<?php
namespace Helhum\Typo3Console\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
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
    protected $sourceTypes = array('array', 'string');

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
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_string($source)) {
            if ($source === '') {
                return array();
            } else {
                return explode($this->getConfiguredStringDelimiter($configuration), $source);
            }
        }

        return $source;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    protected function getConfiguredStringDelimiter(\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_STRING_DELIMITER;
        }
        $stringDelimiter = $configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ArrayConverter', self::CONFIGURATION_STRING_DELIMITER);
        if ($stringDelimiter === null) {
            return self::DEFAULT_STRING_DELIMITER;
        } elseif (!is_string($stringDelimiter)) {
            throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_STRING_DELIMITER must be of type string, "' . (is_object($stringDelimiter) ? get_class($stringDelimiter) : gettype($stringDelimiter)) . '" given', 1368433339);
        }
        return $stringDelimiter;
    }
}
