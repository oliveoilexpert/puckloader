<?php
namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

class IconLoaderUtility
{
    public static function getIconConfigurationFromPath(string $path, string $extensionKey): array
    {
        $iconsPath = ExtensionManagementUtility::extPath($extensionKey) . $path;
        $iconPaths = GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            path: $iconsPath,
            extList: 'svg',
            recursivityLevels: 0
        );
        $configuration = [];
        foreach ($iconPaths as $path) {
            $configuration[$extensionKey . '_' . GeneralUtility::camelCaseToLowerCaseUnderscored(PathUtility::pathinfo($path, PATHINFO_FILENAME))] = [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:' . $extensionKey . '/'. $path . $path
            ];
        }
        return $configuration;
    }
}