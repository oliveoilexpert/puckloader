<?php
namespace UBOS\Puckloader\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

class TcaUtility
{
    protected static int $version = 0;

    public static function getVersion(): int
    {
        if (self::$version) {
            return self::$version;
        }
        $version = GeneralUtility::makeInstance(Typo3Version::class);
        self::$version = $version->getMajorVersion();
        return self::$version;
    }

    public static function selectItemHelper(array $item): array
    {
        if (self::getVersion() < 12) {
            return [
                $item[0] ?? $item['label'],
                $item[1] ?? $item['value'],
                $item[2] ?? $item['icon'] ??  '',
                $item[3] ?? $item['group'] ?? '',
            ];
        }
        return [
            'label' => $item[0] ?? $item['label'],
            'value' => $item[1] ?? $item['value'],
            'icon' => $item[2] ?? $item['icon'] ??  '',
            'group' => $item[3] ?? $item['group'] ?? '',
        ];
    }

    public static function selectItemsHelper(array $items): array
    {
        return array_map(function($item) {
            return self::selectItemHelper($item);
        }, $items);
    }
}