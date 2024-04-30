<?php

namespace UBOS\Puckloader\Loader;

use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use UBOS\Puckloader\Configuration;
use UBOS\Puckloader\Attribute\ModelPersistence;
use UBOS\Puckloader\Utility\TcaUtility;

/**
 * Class PageModelLoader
 */
class PageModelLoader implements LoaderInterface
{
    protected static array $modelRegister = [];

    protected static function getModelRegister(string $extensionKey): array
    {
        if (static::$modelRegister[$extensionKey] ?? null) {
            return static::$modelRegister[$extensionKey];
        }
        $conf = Configuration::get($extensionKey);
        if (!is_dir($conf['pageModel']['path'])) {
            return [];
        }
        $files = GeneralUtility::getFilesInDir($conf['pageModel']['path'], 'php');
        foreach ($files as $key => $file) {
            $files[$key] = PathUtility::pathinfo($file, PATHINFO_FILENAME);
        }
        $files = array_values($files);
        static::$modelRegister[$extensionKey] = [];
        foreach($files as $file) {
            static::$modelRegister[$extensionKey][] = [
                'name' => $file,
                'fullName' => $conf['pageModel']['namespace'] . $file,
                'lowerCaseUnderscored' => GeneralUtility::camelCaseToLowerCaseUnderscored($file),
            ];
        }
        return static::$modelRegister[$extensionKey];
    }

    /**
     * @throws ReflectionException
     */
    public static function buildInformation(string $extensionKey): array
    {
        $return = [];
        foreach(static::getModelRegister($extensionKey) as $model) {
            $refClass = new ReflectionClass($model['fullName']);
            $refModelPersistence = $refClass->getAttributes(ModelPersistence::class)[0] ?? null;
            if ($refModelPersistence?->newInstance()->recordType ?? null) {
                $return[] = [
                    'key' => $refModelPersistence?->newInstance()->recordType,
                    'name' => $model['name'],
                    'lowercaseName' => $model['lowerCaseUnderscored'],
                    'iconIdentifier' => $model['lowerCaseUnderscored'],
                ];
            }
        }
        return $return;
    }

    public static function loadTables(string $extensionKey, array $information): void
    {
    }

    public static function loadTca(string $extensionKey, array $information): void
    {
        $conf = Configuration::get($extensionKey);
        foreach($information as $type) {
            ExtensionManagementUtility::addTcaSelectItem(
                'pages',
                'doktype',
                TcaUtility::selectItemHelper([
                    $conf['languageFile'].':doktype.'.$type['lowercaseName'],
                    $type['key'],
                    $type['iconIdentifier'],
                    'default'
                ]),
                '1',
                'after'
            );
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$type['key']] = $type['iconIdentifier'];
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$type['key'] . '-hideinmenu'] = $type['iconIdentifier']. '_hideinmenu';
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$type['key'] . '-root'] = 'apps-pagetree-page-domain';
        }
    }

    public static function loadConf(string $extensionKey, array $information): void
    {
    }

}