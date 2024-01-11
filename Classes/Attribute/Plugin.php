<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class Plugin
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $name,
        public bool $noCache = false,
        public int $typeNum = 0,
        public array $actions = [],
        public array $noCacheActions = []
    )
    {
    }
}
