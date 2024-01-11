<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UBOS\Puckloader\Configuration;
use UBOS\Puckloader\Attribute\ModelColumn;
use UBOS\Puckloader\Attribute\ModelPersistence;

class ModelLoader implements LoaderInterface
{
    public static function buildInformation(string $extensionKey): array
    {
        $conf = Configuration::get($extensionKey);
        $modelPaths = GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            path: $conf['model']['path'],
            extList: 'php',
            recursivityLevels: 99);
        $sqlStrings = [];
        foreach($modelPaths as $path) {
            $path = str_replace('.php', '', $path);
            $columns = [];
            $fullName = $conf['model']['namespace'] . str_replace('/', '\\', explode($conf['model']['path'], $path)[1]);
            $reflection = new \ReflectionClass($fullName);
            $persistenceAttribute = $reflection->getAttributes(ModelPersistence::class)[0] ?? null;
            $tableName = $persistenceAttribute ? $persistenceAttribute->newInstance()->table : null;
            if (!$tableName) {
                continue;
            }
            foreach($reflection->getProperties() as $property) {
                $columnAttribute = $property->getAttributes(ModelColumn::class)[0] ?? null;
                if (!$columnAttribute) {
                    continue;
                }
                $columnName = $columnAttribute->newInstance()->name
                    ?: GeneralUtility::camelCaseToLowerCaseUnderscored($property->getName());
                $columnDefinition = $columnAttribute->newInstance()->sql;
                $columns[$columnName] = $columnName . ' ' . $columnDefinition;
            }
            if ($columns) {
                $sqlStrings[] = LF . 'CREATE TABLE ' . $tableName . ' (' . LF . implode(',' . LF, $columns) . LF . ');' . LF;
            }
        }
        return [
            'sql' => $sqlStrings,
        ];
    }

    public static function getDatabaseListenerSqlStrings(): array
    {
        $sql = [];
        foreach(Configuration::getAll() as $conf) {
            if ($conf[ModelLoader::class]) {
                $sql = array_merge($sql, static::buildInformation($conf['extensionKey'])['sql']);
            }
        }
        return $sql;
    }

    public static function loadConf(string $extensionKey, array $information): void
    {
    }
    public static function loadTables(string $extensionKey, array $information): void
    {
    }
    public static function loadTca(string $extensionKey, array $information): void
    {
    }
}