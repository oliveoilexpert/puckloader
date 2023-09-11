<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class ContainerElement
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public array $configuration
    )
    {
    }
}
