<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class Plugin
{
    public array $fragment = [
        'typeNum' => 0,
        'noCache' => 0,
    ];
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $name,
        public bool $noCache = false,
        int $typeNum = 0,
        int|array $fragment = 0,
        public array $actions = [],
        public array $noCacheActions = []
    )
    {
        if (is_array($fragment)) {
            $this->fragment = array_merge($this->fragment, $fragment);
        } else {
            $this->fragment['typeNum'] = $fragment;
        }
        if ($typeNum) {
            $this->fragment['typeNum'] = $typeNum;
        }
    }
}
