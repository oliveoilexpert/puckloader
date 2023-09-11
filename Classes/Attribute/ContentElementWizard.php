<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class ContentElementWizard
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $tab,
        public int $order = 10
    )
    {
    }
}
