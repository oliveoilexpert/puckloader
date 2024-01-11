<?php

namespace UBOS\Puckloader\Loader;

interface LoaderInterface
{
    public static function buildInformation(string $extensionKey): array;
    public static function loadTca(string $extensionKey, array $information): void;
    public static function loadConf(string $extensionKey, array $information): void;
    public static function loadTables(string $extensionKey, array $information): void;
}