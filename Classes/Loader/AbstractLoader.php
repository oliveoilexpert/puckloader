<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


abstract class AbstractLoader
{
    protected static array $loaderInformation = [];

    protected static function getLoaderInformation($extensionKey): array
    {
        if (!static::$loaderInformation[$extensionKey] ?? null) {
            static::buildInformation($extensionKey);
        }
        return static::$loaderInformation[$extensionKey];
    }

    protected static function buildInformation(string $extensionKey): void
    {
    }

    abstract public static function loadTca(string $extensionKey): void;
    abstract public static function loadConf(string $extensionKey): void;
    abstract public static function loadTables(string $extensionKey): void;
}