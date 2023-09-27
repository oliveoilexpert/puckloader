<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


abstract class AbstractLoader
{
    abstract public static function buildInformation(string $extensionKey): array;
    abstract public static function loadTca(string $extensionKey, array $information): void;
    abstract public static function loadConf(string $extensionKey, array $information): void;
    abstract public static function loadTables(string $extensionKey, array $information): void;
}