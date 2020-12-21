<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Compatibility;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Utility for dealing with ext_emconf copied from TYPO3 Core to allow 10.4 and 11.x compatibility
 * @deprecated Will be removed, when 10.4 compat is removed
 */
class EmConfReader implements SingletonInterface
{
    /**
     * Returns the $EM_CONF array from an extensions ext_emconf.php file
     *
     * @param string $extensionKey the extension name
     * @param string $absolutePath path to the ext_emconf.php
     * @return array|bool EMconf array values or false if no ext_emconf.php found.
     */
    public function includeEmConf(string $extensionKey, string $absolutePath)
    {
        $_EXTKEY = $extensionKey;
        $path = rtrim($absolutePath, '/') . '/ext_emconf.php';
        $EM_CONF = null;
        if (!empty($absolutePath) && file_exists($path)) {
            include $path;
            if (is_array($EM_CONF[$_EXTKEY])) {
                return $EM_CONF[$_EXTKEY];
            }
        }

        return false;
    }
}
