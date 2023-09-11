<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UBOS\Puckloader\Loader\Configuration;
use UBOS\Puckloader\Loader\ModelLoader;
use UBOS\Puckloader\Loader\ContentModelLoader;
use UBOS\Puckloader\Loader\ControllerLoader;

class Loader
{
    public static function loadConf(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        if ($conf['model']['load']) {
            ModelLoader::loadConf($extensionKey);
        }
        if ($conf['contentModel']['load']) {
            ContentModelLoader::loadConf($extensionKey);
        }
        if ($conf['controller']['load']) {
            ControllerLoader::loadConf($extensionKey);
        }
    }
    public static function loadTables(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        if ($conf['model']['load']) {
            ModelLoader::loadTables($extensionKey);
        }
        if ($conf['contentModel']['load']) {
            ContentModelLoader::loadTables($extensionKey);
        }
        if ($conf['controller']['load']) {
            ControllerLoader::loadTables($extensionKey);
        }
    }

    public static function loadTca(string $extensionKey): void
    {
        $conf = Configuration::get($extensionKey);
        if ($conf['model']['load']) {
            ModelLoader::loadTca($extensionKey);
        }
        if ($conf['contentModel']['load']) {
            ContentModelLoader::loadTca($extensionKey);
        }
        if ($conf['controller']['load']) {
            ControllerLoader::loadTca($extensionKey);
        }
    }
}