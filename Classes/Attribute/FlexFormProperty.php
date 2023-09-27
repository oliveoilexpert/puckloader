<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class FlexFormProperty
{
    public string $flexFormValue;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $flexFormValue = '')
    {
        $this->flexFormValue = $flexFormValue;
    }
}
