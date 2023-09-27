<?php
namespace UBOS\Puckloader\Utility;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use UBOS\Puckloader\Attribute\ModelColumn;
use UBOS\Puckloader\Attribute\ModelPersistence;
use UBOS\Puckloader\Configuration;

class PuckloaderUtility
{
    public static function getIconConfigurationFromPath(string $path, string $extensionKey): array
    {
        $conf = Configuration::get($extensionKey);
        $iconsPath = ExtensionManagementUtility::extPath($extensionKey) . $path;
        $iconPaths = GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            path: $iconsPath,
            extList: 'svg',
            recursivityLevels: 0
        );
        $configuration = [];
        foreach ($iconPaths as $iconPath) {
            $configuration[$conf['iconIdentifierPrefix'] . GeneralUtility::camelCaseToLowerCaseUnderscored(PathUtility::pathinfo($path, PATHINFO_FILENAME))] = [
                'provider' => SvgIconProvider::class,
                'source' => 'EXT:' . $extensionKey . '/'. $path . $iconPath
            ];
        }
        return $configuration;
    }

    public static function getExtbasePersistenceMapping(string $extensionKey): array
    {
        $conf = Configuration::get($extensionKey);
        $mapping = [];
        $children = [];
        $modelPaths = GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            path: $conf['model']['path'],
            extList: 'php',
            recursivityLevels: 99);
        foreach($modelPaths as $path) {
            $path = str_replace('.php', '', $path);
            $fullName = $conf['model']['namespace'] . str_replace('/', '\\', explode($conf['model']['path'], $path)[1]);
            $reflection = new \ReflectionClass($fullName);
            $persistenceAttribute = $reflection->getAttributes(ModelPersistence::class)[0] ?? null;
            $tableName = $persistenceAttribute?->newInstance()?->table;
            if (!$tableName) {
                continue;
            }
            $mapping[$fullName] = [
                'tableName' => $tableName,
            ];
            $parentClass = $persistenceAttribute->newInstance()->parentClass ?? null;
            if ($parentClass) {
                $children[$fullName] = $parentClass;
            }
            $recordType = $persistenceAttribute->newInstance()->recordType ?? null;
            if ($recordType) {
                $mapping[$fullName]['recordType'] = $recordType;
            }
            foreach($reflection->getProperties() as $property) {
                $columnAttribute = $property->getAttributes(ModelColumn::class)[0] ?? null;
                $columnName = $columnAttribute?->newInstance()?->name;
                if ($columnName) {
                    $mapping[$fullName]['properties'][$property->getName()]['fieldName'] = $columnName;
                }
            }
        }

        foreach($children as $child => $parent) {
            $mapping[$parent]['subclasses'][] = $child;
        }

        return $mapping;
    }
}