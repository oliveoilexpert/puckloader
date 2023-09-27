<?php

namespace UBOS\Puckloader;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UBOS\Puckloader\Loader\ModelLoader;
use UBOS\Puckloader\Loader\ContentModelLoader;
use UBOS\Puckloader\Loader\PageModelLoader;
use UBOS\Puckloader\Loader\ControllerLoader;

class Loader
{
    const LOADERS = [
        ModelLoader::class,
        ContentModelLoader::class,
        PageModelLoader::class,
        ControllerLoader::class,
    ];
    public static ?array $loaderInformation = null;

    protected static function getInformationForExtensionAndLoader(string $extensionKey, string $loader): array
    {
        // todo cache
        if (!static::$loaderInformation) {
            static::$loaderInformation = [
                ModelLoader::class => [],
                ContentModelLoader::class => [],
                PageModelLoader::class => [],
                ControllerLoader::class => [],
            ];
        }
        if (!(static::$loaderInformation[$loader][$extensionKey] ?? null)) {
            static::$loaderInformation[$loader][$extensionKey] = $loader::buildInformation($extensionKey);
            //DebugUtility::debug(static::$loaderInformation[$loader][$extensionKey]);
        }
        return static::$loaderInformation[$loader][$extensionKey];
    }

    public static function loadConf(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(self::LOADERS as $loader) {
            if ($conf[$loader]) {
                $loader::loadConf($extensionKey, static::getInformationForExtensionAndLoader($extensionKey,$loader));
            }
        }
    }
    public static function loadTables(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(self::LOADERS as $loader) {
            if ($conf[$loader]) {
                $loader::loadTables($extensionKey, static::getInformationForExtensionAndLoader($extensionKey,$loader));
            }
        }
    }
    public static function loadTca(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(self::LOADERS as $loader) {
            if ($conf[$loader]) {
                $loader::loadTca($extensionKey, static::getInformationForExtensionAndLoader($extensionKey,$loader));
            }
        }
    }
}