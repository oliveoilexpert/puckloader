<?php

namespace UBOS\Puckloader\Loader;

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

    public static function loadConf(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(self::LOADERS as $loader) {
            if ($conf[$loader]) {
                $loader::loadConf($extensionKey);
            }
        }
    }
    public static function loadTables(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(self::LOADERS as $loader) {
            if ($conf[$loader]) {
                $loader::loadTables($extensionKey);
            }
        }
    }

    public static function loadTca(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        foreach(self::LOADERS as $loader) {
            if ($conf[$loader]) {
                $loader::loadTca($extensionKey);
            }
        }
    }
}