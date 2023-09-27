<?php
namespace UBOS\Puckloader\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

class TcaUtility
{

    protected static int $version = 0;

    public static function getVersion(): int
    {
        if (self::$version) {
            return self::$version;
        }
        $version = GeneralUtility::makeInstance(Typo3Version::class);
        self::$version = $version->getMajorVersion();
        return self::$version;
    }
    public const STANDARD_CROP_RATIOS = [
        'free' => [
            'title' => 'free',
            'value' => 'NaN'
        ],
        '1:1' => [
            'title' => '1:1',
            'value' => 1
        ],
        '2:1' => [
            'title' => '2:1',
            'value' => 2
        ],
        '3:1' => [
            'title' => '3:1',
            'value' => 3
        ],
        '3:2' => [
            'title' => '3:2',
            'value' => 3 / 2
        ],
        '4:1' => [
            'title' => '4:1',
            'value' => 4
        ],
        '4:3' => [
            'title' => '4:3',
            'value' => 4 / 3
        ],
        '5:2' => [
            'title' => '5:2',
            'value' => 5 / 2
        ],
        '5:3' => [
            'title' => '5:3',
            'value' => 5 / 3
        ],
        '5:4' => [
            'title' => '5:4',
            'value' => 5 / 4
        ],
        '16:9' => [
            'title' => '16:9',
            'value' => 16 / 9
        ],
        '16:10' => [
            'title' => '16:10',
            'value' => 16 / 10
        ]
    ];

    /**
     * Return cropVariant array for TCA.
     * If $allowedRatios isn't set, the key is used as ratio, e.g. '16:9' => ['title' => '16:9', 'value' => 16/9]
     * @param string $identifier
     * @param array|string|null $allowedRatios
     * @return array
     *
     */
    public static function getCropVariant(string $key, array|string|null $allowedRatios = null, $disabled = false): array
    {
        if ($allowedRatios === 'standard') {
            return [
                'title' => $key,
                'disabled' => $disabled,
                'allowedAspectRatios' => self::STANDARD_CROP_RATIOS,
            ];
        }
        if (is_string($allowedRatios) && isset(explode(',', $allowedRatios)[1]) ) {
            $allowedRatios = explode(',', $allowedRatios);
        }
        if (!is_array($allowedRatios)) {
            $allowedRatios = [$key];
        }
        $allowedAspectRatios = [];
        foreach($allowedRatios as $ratio) {
            $ratioArr = explode(':', $ratio);
            $dividend = floatval($ratioArr[0]) ?: 1;
            $divisor = floatval($ratioArr[1]) ?: 1;
            $allowedAspectRatios[$ratio] = [
                'title' => $ratio,
                'value' => $dividend /$divisor
            ];
        }
        return [
            'title' => $key,
            'disabled' => $disabled,
            'allowedAspectRatios' => $allowedAspectRatios,
        ];
    }

    public static function getCropVariants(array|string $variants): array
    {
        $cropVariants = [];
        if (!is_array($variants)) {
            $variants = explode(',', $variants);
        }
        foreach($variants as $variant) {
            $key = $variant['key'] ?? $variant;
            $cropVariants[$key] = self::getCropVariant($key, $variant['allowedRatios'] ?? null, $variant['disabled'] ?? false);

        }
        return $cropVariants;
    }

    public static function getCropVariantConfigOverride(array|string $variants, array|string $disableVariants): array
    {
        $cropVariants = self::getCropVariants($variants);
        if (!is_array($disableVariants)) {
            $disableVariants = explode(',', $disableVariants);
        }
        foreach($disableVariants as $key) {
            $cropVariants[$key] = ['disabled' => true];
        }
        return [
            'config' => [
                'overrideChildTca' => [
                    'columns' => [
                        'crop' => [
                            'config' => [
                                'cropVariants' => $cropVariants
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }

    public static function getContentShowitemBase(): string
    {
        return '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,';
    }

    public static function selectItemsHelper(array $items): array
    {
        $v12Items = [];
        foreach($items as $item) {
            $v12Items[] = self::selectItemHelper($item);
        }
        return $v12Items;
    }

    public static function selectItemHelper(array $item): array
    {
        if (self::getVersion() < 12) {
            return $item;
        }
        return [
            'label' => $item[0] ?? $item['label'],
            'value' => $item[1] ?? $item['value'],
            'icon' => $item[2] ?? $item['icon'] ??  '',
            'group' => $item[3] ?? $item['group'] ?? '',
        ];
    }
}