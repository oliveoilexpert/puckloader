<?php

declare(strict_types=1);

namespace UBOS\Puckloader\Attribute;

#[\Attribute]
class ModelColumn
{
    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public string $sql = 'text',
        public string $name = ''
    )
    {
        $this->sql = $this->shortHandMapper($this->sql);
    }

    protected function shortHandMapper(string $sql): string
    {
        $mapper = [
            'string' => 'varchar(255) DEFAULT \'\' NOT NULL',
            'int' => 'int(11) DEFAULT \'0\' NOT NULL',
            'bool' => 'tinyint(4) unsigned DEFAULT \'0\' NOT NULL',
            'boolean' => 'tinyint(4) unsigned DEFAULT \'0\' NOT NULL',
            'text' => 'text',
            'link' => 'varchar(1024) DEFAULT \'\' NOT NULL',
            'array' => 'text',
            'datetime' => 'int(11) UNSIGNED DEFAULT \'0\' NOT NULL',
            'Object' => 'varchar(255) DEFAULT \'\' NOT NULL',
            'FileReference' => 'int(11) DEFAULT \'0\' NOT NULL',
            'ObjectStorage' => 'varchar(255) DEFAULT \'\' NOT NULL',

            'varchar255' => 'varchar(255) DEFAULT \'\' NOT NULL',
            'varchar1024' => 'varchar(1024) DEFAULT \'\' NOT NULL',

            'tinyint' => 'tinyint(4) DEFAULT \'0\' NOT NULL',
            'float' => 'float DEFAULT \'0\' NOT NULL',
            'double' => 'double DEFAULT \'0\' NOT NULL',
            'decimal' => 'decimal(10,2) DEFAULT \'0\' NOT NULL',
            'date' => 'date DEFAULT \'0000-00-00\' NOT NULL',
            'time' => 'time DEFAULT \'00:00:00\' NOT NULL',
            'year' => 'year(4) DEFAULT \'0000\' NOT NULL',
            'timestamp' => 'timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'enum' => 'enum(\'0\',\'1\') DEFAULT \'0\' NOT NULL',
            'set' => 'set(\'0\',\'1\') DEFAULT \'0\' NOT NULL',
            'json' => 'json DEFAULT \'\' NOT NULL',
        ];
        return $mapper[$sql] ?? $sql;
    }
}
