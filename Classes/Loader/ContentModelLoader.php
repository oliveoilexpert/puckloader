<?php

namespace UBOS\Puckloader\Loader;

use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use UBOS\Puckloader\Configuration;
use UBOS\Puckloader\Attribute\ContainerElement;
use UBOS\Puckloader\Attribute\ContentElementWizard;
use UBOS\Puckloader\Attribute\PluginElement;
use UBOS\Puckloader\Attribute\FlexFormProperty;
use UBOS\Puckloader\Utility\TcaUtility;

/**
 * Class ContentModelLoader <br>
 * Loads all classes from the Content  folder <br>
 * Registers them as content elements based on class attributes ContentElementWizard, ContainerElement and PluginElement <br>
 * Registering them as content elements is done by adding them to the CType select, New Content Element Wizard, defining the frontend typoscript
 */
class ContentModelLoader extends AbstractLoader
{
    protected static array $modelRegister = [];

    protected static function getModelRegister(string $extensionKey): array
    {
        if (static::$modelRegister[$extensionKey] ?? null) {
            return static::$modelRegister[$extensionKey];
        }
        $conf = Configuration::get($extensionKey);
        if (!is_dir($conf['contentModel']['path'])) {
            return [];
        }
        $files = GeneralUtility::getFilesInDir($conf['contentModel']['path'], 'php');
        foreach ($files as $key => $file) {
            $files[$key] = PathUtility::pathinfo($file, PATHINFO_FILENAME);
        }
        $files = array_values($files);
        static::$modelRegister[$extensionKey] = [];
        foreach($files as $file) {
            static::$modelRegister[$extensionKey][] = [
                'name' => $file,
                'fullName' => $conf['contentModel']['namespace'] . $file,
                'typeKey' => $conf['cTypePrefix'] . GeneralUtility::camelCaseToLowerCaseUnderscored($file),
                'lowerCaseUnderscored' => GeneralUtility::camelCaseToLowerCaseUnderscored($file),
            ];
        }
        return static::$modelRegister[$extensionKey];
    }

    /**
     * @throws ReflectionException
     */
    public static function buildInformation(string $extensionKey): array
    {
        $groupKeys = [];
        $types = [];
        $conf = Configuration::get($extensionKey);
        $wizardGroups = static::sortModelsByWizardTabAndOrder(static::getModelRegister($extensionKey));
        foreach($wizardGroups as $groupKey => $group) {

            // set groups
            $groupKeys[] = $groupKey;
            foreach($group as $model) {

                $refClass = new ReflectionClass($model['fullName']);
                $refPluginElement = $refClass->getAttributes(PluginElement::class)[0] ?? null;
                $refContainerElement = $refClass->getAttributes(ContainerElement::class)[0] ?? null;
                $pluginName = $refPluginElement?->newInstance()->pluginName;
                $flexformProperties = [];
                foreach ($refClass->getProperties() as $property) {
                    $refFlexFormProperty = $property->getAttributes(FlexFormProperty::class) ?? null;
                    if ($refFlexFormProperty) {
                        $column = GeneralUtility::camelCaseToLowerCaseUnderscored($property->getName());
                        $flexFormValue = $refFlexFormProperty[0]->newInstance()->flexFormValue;
                        if ($flexFormValue) {
                            $flexformProperties[$column] = $flexFormValue;
                        }
                    }
                }

                // set types
                $type = [
                    'key' => $model['typeKey'],
                    'groupKey' => $groupKey,
                    'name' => $model['name'],
                    'lowercaseName' => $model['lowerCaseUnderscored'],
                    'iconIdentifier' => $model['lowerCaseUnderscored'],
                    'pluginName' => $pluginName ?? $conf['contentModel']['pluginName'],
                    'extensionName' => $pluginName ? ($refPluginElement?->newInstance()->extensionName ?: $conf['extensionName']) : $conf['contentModel']['extensionName'],
                    'vendorName' => $pluginName ? ($refPluginElement?->newInstance()->vendorName ?: $conf['vendorName']) : $conf['contentModel']['vendorName'],
                    'piFlexFormValue' => $refPluginElement?->newInstance()?->piFlexFormValue,
                    'containerConfiguration' => $refContainerElement?->newInstance()?->configuration,
                    'containerDataProcessing' => '',
                    'flexformProperties' => $flexformProperties,
                ];

                if (is_array($type['containerConfiguration'])) {
                    foreach($type['containerConfiguration'] as $row) {
                        foreach($row as $column) {
                            $type['containerDataProcessing'] .= '
                    '.$column['colPos'].' = B13\Container\DataProcessing\ContainerProcessor
                    '.$column['colPos'].' {
                        colPos = '.$column['colPos'].'
                        as = children_'.$column['colPos'].'
                    }
                    ';
                        }
                    }
                }
                $types[] = $type;
            }
        }
        return [
            'groupKeys' => $groupKeys,
            'types' => $types,
        ];
    }

    public static function loadTables(string $extensionKey, array $information): void
    {
        $conf = Configuration::get($extensionKey);
        foreach($information['groupKeys'] as $key) {
            ExtensionManagementUtility::addPageTSConfig('
                mod.wizards.newContentElement.wizardItems.'.$key.' {
                  header = '.$conf['languageFile'].':wizard.'.$key.'.header
                }
            ');
        }
        foreach($information['types'] as $type) {
            ExtensionManagementUtility::addPageTSConfig('
                mod.wizards.newContentElement.wizardItems.' . $type['groupKey'] . '.elements.' . $type['key'] . ' {
                        iconIdentifier = ' . $type['iconIdentifier'] . '
                        title = ' . $conf['languageFile'] . ':wizard.' . $type['lowercaseName'] . '
                        description = ' . $conf['languageFile'] . ':wizard.' . $type['lowercaseName'] . '.description
                        tt_content_defValues {
                            CType = ' . $type['key'] . '
                        }
                }
                mod.wizards.newContentElement.wizardItems.' . $type['groupKey'] . '.show := addToList(' . $type['key'] . ')
            ');

        }
    }

    public static function loadTca(string $extensionKey, array $information): void
    {
        $conf = Configuration::get($extensionKey);
        foreach($information['groupKeys'] as $key) {
            ExtensionManagementUtility::addTcaSelectItemGroup(
                'tt_content',
                'CType',
                $key,
                $conf['languageFile'].'wizard.'.$key.'.header',
            );
        }
        foreach($information['types'] as $type) {

            if ($type['piFlexFormValue']) {
                ExtensionManagementUtility::addPiFlexFormValue(
                    '*',
                    $type['piFlexFormValue'],
                    $type['key']
                );
            }
            foreach ($type['flexformProperties'] as $column => $value) {
                if (is_array($GLOBALS['TCA']['tt_content']['columns']) && is_array($GLOBALS['TCA']['tt_content']['columns'][$column]['config']['ds'])) {
                    $GLOBALS['TCA']['tt_content']['columns'][$column]['config']['ds']['*' . ',' . $type['key']] = $value;
                }
            }
            if ($type['containerConfiguration']) {
                //$previousFieldDefinition = $GLOBALS['TCA']['tt_content']['types'][$type['key']];
                GeneralUtility::makeInstance(Registry::class)->configureContainer(
                    (
                    new ContainerConfiguration(
                        $type['key'], // CType
                        $conf['languageFile'].':content.element.' . $type['lowercaseName'],
                        $conf['languageFile'].':wizard.' . $type['lowercaseName'] . '.description', // description
                        $type['containerConfiguration'] // configuration
                    )
                    )
                        ->setIcon('EXT:' . $extensionKey . '/Resources/Public/Icons/Backend/' . $type['name'] . '.svg')
                        ->SetGroup($type['groupKey'])
                        ->setRegisterInNewContentElementWizard(false)
                );
                //$GLOBALS['TCA']['tt_content']['types'][$type['key']] = $previousFieldDefinition;
                // what do here? to do update
            } else {
                ExtensionManagementUtility::addTcaSelectItem(
                    'tt_content',
                    'CType',
                    TcaUtility::selectItemHelper([
                        $conf['languageFile'].':content.element.'.$type['lowercaseName'],
                        $type['key'],
                        $type['iconIdentifier'],
                        $type['groupKey'],
                    ]),
                );
            }
            if ($conf['contentModel']['previewRenderer']) {
                $GLOBALS['TCA']['tt_content']['types'][$type['key']]['previewRenderer'] = $conf['contentModel']['previewRenderer'];
            }
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$type['key']] = $type['iconIdentifier'];
        }

        // to do update => container fix for new tca select item format
        foreach($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $key => $item) {
            if (!isset($item['value'])) {
                $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][$key] = TcaUtility::selectItemHelper([
                    $item[0],
                    $item[1],
                    $item[2] ?? '',
                    $item[3] ?? '',
                ]);
            }
        }
        foreach($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'] as $key => $item) {
            if (!isset($item['value'])) {
                $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'][$key] = TcaUtility::selectItemHelper([
                    $item[0],
                    $item[1],
                    $item[2] ?? '',
                    $item[3] ?? '',
                ]);
            }
        }
    }

    public static function loadConf(string $extensionKey, array $information): void
    {
        $conf = Configuration::get($extensionKey);
        foreach($information['types'] as $type) {
            ExtensionManagementUtility::addTypoScript(
                $extensionKey,
                'setup',
                '
        tt_content.'.$type['key'].' = COA
        tt_content.'.$type['key'].'.20 = USER
        tt_content.'.$type['key'].'.20  {
                userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
                extensionName = ' . $type['extensionName'] . '
                pluginName = ' . $type['pluginName'] . '
                vendorName = ' . $type['vendorName'] . '
                settings {
                    modelName = ' . $type['name'] . '
                    modelNamespace = ' . $conf['contentModel']['namespace'] . '
                    dataProcessing {
                        '.$type['containerDataProcessing'].'
                    }
                    view {
                        templateRootPath = ' . $conf['contentModel']['templateRootPath'] . '
                        partialRootPath = ' . $conf['contentModel']['partialRootPath'] . '
                        layoutRootPath = ' . $conf['contentModel']['layoutRootPath'] . '
                    }
                }   
        }',
                'defaultContentRendering'
            );
        }

    }

    /**
     * @throws ReflectionException
     */
    protected static function sortModelsByWizardTabAndOrder(array $models): array
    {
        $wizardGroups = [];
        foreach($models as $model) {
            $tab = '01_content';
            $order = 10;
            $refClass = new ReflectionClass($model['fullName']);
            $refContentElementWizard = $refClass->getAttributes(ContentElementWizard::class)[0] ?? null;
            if ($refContentElementWizard) {
                $contentElementWizard = $refContentElementWizard->newInstance();
                $tab = $contentElementWizard->tab ?? $tab;
                $order = $contentElementWizard->order ?? $order;
            }
            if (!isset($wizardGroups[$tab])) {
                $wizardGroups[$tab] = [];
            }
            if (!isset($wizardGroups[$tab][$order])) {
                $wizardGroups[$tab][$order] = $model;
            } else {
                $wizardGroups[$tab][] = $model;
            }
        }
        ksort($wizardGroups);
        foreach($wizardGroups as $groupKey => $group) {
            ksort($wizardGroups[$groupKey]);
        }
        return $wizardGroups;
    }

}