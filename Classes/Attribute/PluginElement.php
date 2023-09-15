<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class PluginElement
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $pluginName,
        public string $piFlexFormValue = '',
        public string $extensionName = '',
        public string $vendorName = ''
    )
    {
    }
}
