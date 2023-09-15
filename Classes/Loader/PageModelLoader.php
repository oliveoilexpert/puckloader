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

/**
 * Class PageModelLoader
 */
class PageModelLoader extends AbstractLoader
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
    protected static function buildInformation(string $extensionKey): void
    {
        foreach(static::getModelRegister($extensionKey) as $model) {
            $refClass = new ReflectionClass($model['fullName']);
            $refModelPersistence = $refClass->getAttributes(ModelPersistence::class)[0] ?? null;
            if ($refModelPersistence?->newInstance()->recordType ?? null) {
                static::$loaderInformation[$extensionKey][] = [
                    'key' => $refModelPersistence?->newInstance()->recordType,
                    'name' => $model['name'],
                    'lowercaseName' => $model['lowerCaseUnderscored'],
                    'iconIdentifier' => $model['lowerCaseUnderscored'],
                ];
            }
        }
    }

    public static function loadTables(string $extensionKey): void
    {
    }

    public static function loadTca(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(static::getLoaderInformation($extensionKey) as $type) {
            ExtensionManagementUtility::addTcaSelectItem(
                'pages',
                'doktype',
                [
                    'label' => $conf['languageFile'].':doktype.'.$type['lowercaseName'],
                    'value' => $type['key'],
                    'icon' => $type['iconIdentifier'],
                ],
                '1',
                'after'
            );
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$type['key']] = $type['iconIdentifier'];
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$type['key'] . '-hideinmenu'] = $type['iconIdentifier'. '_hideinmenu'];
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$type['key'] . '-root'] = 'apps-pagetree-page-domain';
        }
    }

    public static function loadConf(string $extensionKey): void
    {
    }

}