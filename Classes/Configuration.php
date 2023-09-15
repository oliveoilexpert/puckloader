<?php

namespace UBOS\Puckloader;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;


class Configuration
{
    protected static array $configurations = [];
    protected static function buildConfigurationFromYaml(string $extensionKey): void
    {
        // todo cache
        $yaml = YamlFileLoader::load(ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/Puckloader.yaml');
        $extPath = ExtensionManagementUtility::extPath($yaml['extensionKey']);
        $extNamespace = $yaml['vendorName'] . '\\' . GeneralUtility::underscoredToUpperCamelCase($yaml['extensionKey']) . '\\';
        $modelPath = $extPath . ($yaml['model']['path'] ?: 'Classes/Domain/Model/');
        $modelNamespace = $yaml['model']['namespace'] ?: $extNamespace . 'Domain\\Model\\';
        static::$configurations[$extensionKey] = [
            ModelLoader::class => in_array('model', $yaml['loader']),
            ControllerLoader::class => in_array('controller', $yaml['loader']),
            ContentModelLoader::class => in_array('contentModel', $yaml['loader']),
            PageModelLoader::class => in_array('pageModel', $yaml['loader']),
            'vendorName' => $yaml['vendorName'],
            'extensionKey' => $yaml['extensionKey'],
            'extensionName' => GeneralUtility::underscoredToUpperCamelCase($yaml['extensionKey']),
            'model' => [
                'path' => $modelPath,
                'namespace' => $modelNamespace,
            ],
            'contentModel' => [
                'path' => $extPath . $yaml['contentModel']['path'] ?: $modelPath . '/Content',
                'namespace' => $yaml['contentModel']['namespace'] ?: $modelNamespace . 'Content\\',
                'pluginName' => $yaml['contentModel']['pluginName'] ?: 'Content',
                'extensionName' => $yaml['contentModel']['extensionName'] ?: 'Puckloader',
                'vendorName' => $yaml['contentModel']['vendorName'] ?: 'UBOS',
                'templateRootPath' => $yaml['contentModel']['templateRootPath'] ?: 'EXT:' . $yaml['extensionKey'] . '/Resources/Private/Templates/',
                'partialRootPath' => $yaml['contentModel']['partialRootPath'] ?: 'EXT:' . $yaml['extensionKey'] . '/Resources/Private/Partials/',
                'layoutRootPath' => $yaml['contentModel']['layoutRootPath'] ?: 'EXT:' . $yaml['extensionKey'] . '/Resources/Private/Layouts/',
            ],
            'pageModel' => [
                'path' => $extPath . $yaml['pageModel']['path'] ?: $modelPath . '/Page',
                'namespace' => $yaml['pageModel']['namespace'] ?: $modelNamespace . 'Page\\',
            ],
            'controller' => [
                'path' => $extPath . $yaml['controller']['path'] ?: 'Classes/Controller/',
                'namespace' => $yaml['controller']['namespace'] ?: $extNamespace . 'Controller\\',
            ],
            'languageFile' => $yaml['languageFile'] ?: 'LLL:EXT:' . $yaml['extensionKey'] . '/Resources/Private/Language/locallang_be.xlf',
            'iconIdentifierPrefix' => $yaml['iconIdentifierPrefix'] ?: $yaml['extensionKey'] . '_',
            'cTypePrefix' => $yaml['cTypePrefix'] ?: $yaml['extensionKey'] . '_',
        ];
    }

    public static function get(string $extensionKey): array
    {
        if (!static::$configurations[$extensionKey] ?? null) {
            static::buildConfigurationFromYaml($extensionKey);
        }
        return static::$configurations[$extensionKey];
    }

    public static function getAll(): array
    {
        return static::$configurations;
    }
}