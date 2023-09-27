<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use UBOS\Puckloader\Configuration;
use UBOS\Puckloader\Attribute\Plugin;

class ControllerLoader extends AbstractLoader
{
    public static function buildInformation($extensionKey): array
    {
        $conf = Configuration::get($extensionKey);
        $controllerPaths = GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            path: $conf['controller']['path'],
            extList: 'php',
            excludePattern: '^(?!.*Controller\.php$).*$'
        );
        $return = [];
        foreach ($controllerPaths as $path) {
            $path = str_replace('.php', '', $path);
            $fullName = $conf['controller']['namespace'] . str_replace('/', '\\', explode($conf['controller']['path'], $path)[1]);
            $reflection = new \ReflectionClass($fullName);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $pluginAttribute = $method->getAttributes(Plugin::class)[0] ?? null;
                if (!$pluginAttribute || !$pluginAttribute->newInstance()->name) {
                    continue;
                }
                $pluginAttributeInstance = $pluginAttribute->newInstance();
                $lowerCaseName = GeneralUtility::camelCaseToLowerCaseUnderscored($pluginAttributeInstance->name);
                $actionName = str_replace('Action', '', $method->getName());
                $cacheableActions = $pluginAttributeInstance->cacheableActions ?? [];
                $nonCacheableActions = $pluginAttributeInstance->nonCacheableActions ?? [];
                if (isset($cacheableActions[$fullName])) {
                    $cacheableActions[$fullName] = $cacheableActions[$fullName] . ',' . $actionName;
                } else {
                    $cacheableActions[$fullName] = $actionName;
                }
                if ($pluginAttributeInstance->noCache) {
                    if (isset($nonCacheableActions[$fullName])) {
                        $nonCacheableActions[$fullName] = $nonCacheableActions[$fullName] . ',' . $actionName;
                    } else {
                        $nonCacheableActions[$fullName] = $actionName;
                    }
                }
                $return[] = [
                    'extensionKey' => $extensionKey,
                    'pluginKey' => $pluginAttributeInstance->name,
                    'controllerFullName' => $fullName,
                    'actionName' => $actionName,
                    'label' => 'LLL:EXT:puck/Resources/Private/Language/locallang_be.xlf:plugin.'.$lowerCaseName,
                    'icon' => 'puck_plugin_'.$lowerCaseName,
                    'groupKey' => $extensionKey,
                    'cacheableActions' => $cacheableActions,
                    'nonCacheableActions' => $nonCacheableActions,
                ];
            }
        }
        return $return;
    }

    public static function loadConf(string $extensionKey, array $information): void
    {
        foreach ($information as $plugin) {
            ExtensionUtility::configurePlugin(
                $plugin['extensionKey'],
                $plugin['pluginKey'],
                $plugin['cacheableActions'],
                $plugin['nonCacheableActions']
            );
        }
    }

    public static function loadTables(string $extensionKey, array $information): void
    {
    }

    public static function loadTca(string $extensionKey, array $information): void
    {
        foreach ($information as $plugin) {
            ExtensionUtility::registerPlugin(
                $plugin['extensionKey'],
                $plugin['pluginKey'],
                $plugin['label'],
                $plugin['icon'],
                $plugin['groupKey']
            );
        }
    }

}