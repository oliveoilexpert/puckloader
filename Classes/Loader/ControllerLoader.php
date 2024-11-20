<?php

namespace UBOS\Puckloader\Loader;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use UBOS\Puckloader\Configuration;
use UBOS\Puckloader\Attribute\Plugin;

class ControllerLoader implements LoaderInterface
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
                $actions = $pluginAttributeInstance->actions ?? [];
                $noCacheActions = $pluginAttributeInstance->noCacheActions ?? [];
                if (isset($actions[$fullName])) {
                    $actions[$fullName] = $actionName . ',' . $actions[$fullName];
                } else {
                    $actions[$fullName] = $actionName;
                }
                if ($pluginAttributeInstance->noCache) {
                    if (isset($noCacheActions[$fullName])) {
                        $noCacheActions[$fullName] = $actionName . ',' . $noCacheActions[$fullName];
                    } else {
                        $noCacheActions[$fullName] = $actionName;
                    }
                }
                $return[] = [
                    'extensionKey' => $extensionKey,
                    'pluginKey' => $pluginAttributeInstance->name,
                    'controllerFullName' => $fullName,
                    'actionName' => $actionName,
                    'label' => $conf['languageFile'] . ':plugin.'.$lowerCaseName,
                    'icon' => 'puck_plugin_'.$lowerCaseName,
                    'signature' => $extensionKey . '_' . str_replace('_', '', $lowerCaseName),
                    'groupKey' => $extensionKey,
                    'actions' => $actions,
                    'noCacheActions' => $noCacheActions,
                    'fragment' => $pluginAttributeInstance->fragment,
                    'extensionName' => $conf['extensionName'],
                    'vendorName' => $conf['vendorName'],
                    'register' => $conf['controller']['register'],
                ];
            }
        }
        return $return;
    }

    public static function loadConf(string $extensionKey, array $information): void
    {
        foreach ($information as $plugin) {
            if ($plugin['fragment']['typeNum']) {
                ExtensionManagementUtility::addTypoScript(
                    $extensionKey,
                    'setup',
                    '
            ' . $plugin['pluginKey'] . 'PluginFragmentPage = PAGE
            ' . $plugin['pluginKey'] . 'PluginFragmentPage {
                typeNum = ' . $plugin['fragment']['typeNum'] . '
                20 = USER
                20 {
                    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
                    extensionName = ' . $plugin['extensionName'] . '
                    vendorName = ' . $plugin['vendorName'] . '
                    pluginName = ' . $plugin['pluginKey'] . '
                }
                meta {
                    robots = noindex, nofollow
                    robots.replace = 1
                }
                config {
                    disableAllHeaderCode = 1
                    debug = 0
                    admPanel = 0
                    index_enable = 0
                    no_cache = '. $plugin['fragment']['noCache'] . '
                }
            }
        ',
                    'defaultContentRendering'
                );
            }
            ExtensionUtility::configurePlugin(
                $plugin['extensionKey'],
                $plugin['pluginKey'],
                $plugin['actions'],
                $plugin['noCacheActions']
            );
        }
    }

    public static function loadTables(string $extensionKey, array $information): void
    {
    }

    public static function loadTca(string $extensionKey, array $information): void
    {
        foreach ($information as $plugin) {
            if (!$plugin['register']) {
                continue;
            }
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