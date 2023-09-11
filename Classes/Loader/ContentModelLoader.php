<?php

namespace UBOS\Puckloader\Loader;

use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use UBOS\Puckloader\Attribute\ContainerElement;
use UBOS\Puckloader\Attribute\ContentElementWizard;
use UBOS\Puckloader\Attribute\PluginElement;
use UBOS\Puckloader\Preview\PuckPreviewRenderer;
use UBOS\Puckloader\Utility\PuckUtility;

/**
 * Class ContentModelLoaderUtility <br>
 * Loads all classes from the puck/Classes/Domain/Model/Content folder <br>
 * Registers them as content elements based on class attributes ContentElementWizard, ContainerElement and PluginElement <br>
 * Registering them as content elements is done by adding them to the CType select, New Content Element Wizard, defining the frontend typoscript
 */
class ContentModelLoader extends AbstractLoader
{
    // array of content model base information
    protected static array $modelRegister = [];

    protected static function getModelRegister(string $extensionKey): array
    {
        if (static::$modelRegister[$extensionKey] ?? null) {
            return static::$modelRegister[$extensionKey];
        }
        $conf = Configuration::get($extensionKey);
        $files = PuckUtility::getBaseFilesInDir($conf['contentModel']['path'], 'php');
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
    protected static function buildInformation(string $extensionKey): void
    {
        $groupKeys = [];
        $types = [];
        $wizardGroups = static::sortModelsByWizardTabAndOrder(static::getModelRegister($extensionKey));
        foreach($wizardGroups as $groupKey => $group) {

            // set groups
            $groupKeys[] = $groupKey;
            foreach($group as $model) {

                $refClass = new ReflectionClass($model['fullName']);
                $refPluginElement = $refClass->getAttributes(PluginElement::class)[0] ?? null;
                $refContainerElement = $refClass->getAttributes(ContainerElement::class)[0] ?? null;

                // set types
                $type = [
                    'key' => $model['typeKey'],
                    'groupKey' => $groupKey,
                    'name' => $model['name'],
                    'lowercaseName' => $model['lowerCaseUnderscored'],
                    'iconIdentifier' => $model['lowerCaseUnderscored'],
                    'pluginName' => $refPluginElement?->newInstance()->pluginName ?? 'Content',
                    'piFlexFormValue' => $refPluginElement?->newInstance()?->piFlexFormValue,
                    'containerConfiguration' => $refContainerElement?->newInstance()?->configuration,
                    'containerDataProcessing' => '',
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
        static::$loaderInformation[$extensionKey] = [
            'groupKeys' => $groupKeys,
            'types' => $types,
        ];
    }

    public static function loadTables(string $extensionKey): void
    {
        $loaderInformation = static::getLoaderInformation($extensionKey);
        $conf = Configuration::get($extensionKey);
        foreach($loaderInformation['groupKeys'] as $key) {
            ExtensionManagementUtility::addPageTSConfig('
                mod.wizards.newContentElement.wizardItems.'.$key.' {
                  header = '.$conf['languageFile'].':wizard.'.$key.'.header
                }
            ');
        }
        foreach($loaderInformation['types'] as $type) {
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

    public static function loadTca(string $extensionKey): void
    {
        $loaderInformation = static::getLoaderInformation($extensionKey);
        $conf = Configuration::get($extensionKey);
        foreach($loaderInformation['groupKeys'] as $key) {
            ExtensionManagementUtility::addTcaSelectItemGroup(
                'tt_content',
                'CType',
                $key,
                $conf['languageFile'].'wizard.'.$key.'.header',
            );
        }
        foreach($loaderInformation['types'] as $type) {

            if ($type['piFlexFormValue']) {
                ExtensionManagementUtility::addPiFlexFormValue(
                    '*',
                    $type['piFlexFormValue'],
                    $type['key']
                );
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
                $GLOBALS['TCA']['tt_content']['types'][$type['key']]['previewRenderer'] = PuckPreviewRenderer::class;
            } else {
                ExtensionManagementUtility::addTcaSelectItem(
                    'tt_content',
                    'CType',
                    [
                        'label' => $conf['languageFile'].':content.element.'.$type['lowercaseName'],
                        'value' => $type['key'],
                        'icon' => $type['iconIdentifier'],
                        'group' => $type['groupKey'],
                    ],
                );
            }
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$type['key']] = $type['iconIdentifier'];
        }
        // to do update => container fix for new tca select item format
        foreach($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $key => $item) {
            if (!isset($item['value'])) {
                $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][$key] = [
                    'label' => $item[0],
                    'value' => $item[1],
                    'icon' => $item[2] ?? '',
                    'group' => $item[3] ?? '',
                ];
            }
        }
        foreach($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'] as $key => $item) {
            if (!isset($item['value'])) {
                $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'][$key] = [
                    'label' => $item[0],
                    'value' => $item[1],
                    'icon' => $item[2] ?? '',
                    'group' => $item[3] ?? '',
                ];
            }
        }
    }

    public static function loadConf(string $extensionKey): void
    {
        $loaderInformation = static::getLoaderInformation($extensionKey);
        foreach($loaderInformation['types'] as $type) {
            ExtensionManagementUtility::addTypoScript(
                $extensionKey,
                'setup',
                '
        tt_content.'.$type['key'].' = COA
        tt_content.'.$type['key'].'.20 = USER
        tt_content.'.$type['key'].'.20  {
                userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
                extensionName = Puck
                pluginName = ' . $type['pluginName'] . '
                vendorName = UBOS
                settings {
                    contentElement = '.$type['name'].'
                    extensionKey = puck
                    vendorName = UBOS
                    dataProcessing {
                        '.$type['containerDataProcessing'].'
                    }
                    view {
                        templateRootPath = EXT:' . $extensionKey . '/Resources/Private/Fluid/
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