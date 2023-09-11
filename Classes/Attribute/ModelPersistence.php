<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class ModelPersistence
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $table,
        public ?string $parentClass = null,
        public ?string $recordType = null,
    )
    {
    }
}
