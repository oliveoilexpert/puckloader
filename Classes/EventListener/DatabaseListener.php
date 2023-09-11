<?php

namespace UBOS\Puckloader\EventListener;

use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use UBOS\Puckloader\Loader\ModelLoader;
use UBOS\Puckloader\Loader\Configuration;


final class DatabaseListener
{
    public function __invoke(AlterTableDefinitionStatementsEvent $event): AlterTableDefinitionStatementsEvent
    {
        $before = $event->getSqlData();
        $event->setSqlData(
            array_merge(
                $before,
                ModelLoader::getDatabaseListenerSqlStrings()
            )
        );
        return $event;
    }
}