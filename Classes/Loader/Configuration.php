<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;


class Configuration
{
    protected static array $ext = [];
    protected static function buildConfigurationFromYaml(string $extensionKey): void
    {
        $yaml = YamlFileLoader::load(ExtensionManagementUtility::extPath($extensionKey) . 'Configuration/Puckloader.yaml');
        $extPath = ExtensionManagementUtility::extPath($yaml['extensionKey']);
        $extNamespace = $yaml['vendorName'] . '\\' . GeneralUtility::underscoredToUpperCamelCase($yaml['extensionKey']) . '\\';
        static::$ext[$extensionKey] = [
            'vendorName' => $yaml['vendorName'],
            'extensionKey' => $yaml['extensionKey'],
            'model' => [
                'load' => in_array('model', $yaml['loader']),
                'path' => $extPath . $yaml['model']['path'] ?: 'Classes/Domain/Model/',
                'namespace' => $yaml['model']['namespace'] ?: $extNamespace . 'Domain\\Model\\',
            ],
            'contentModel' => [
                'load' => in_array('contentModel', $yaml['loader']),
                'path' => $extPath . $yaml['contentModel']['path'] ?: 'Classes/Domain/Model/Content/',
                'namespace' => $yaml['contentModel']['namespace'] ?:$extNamespace . 'Domain\\Model\\Content\\',
            ],
            'controller' => [
                'load' => in_array('controller', $yaml['loader']),
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
        if (!static::$ext[$extensionKey] ?? null) {
            static::buildConfigurationFromYaml($extensionKey);
        }
        return static::$ext[$extensionKey];
    }

    public static function getAll(): array
    {
        return static::$ext;
    }
}