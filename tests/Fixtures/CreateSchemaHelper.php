<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Fixtures;

use Doctrine\DBAL\Connection;

class CreateSchemaHelper
{
    public static function createSchema(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            CREATE TABLE `wfd_meta` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `wfd_table_id` INTEGER NOT NULL DEFAULT 0,
              `data_id` INTEGER NOT NULL DEFAULT 0,
              `wfd_recordtype_id` INTEGER DEFAULT NULL,
              `created_wfd_user_id` INTEGER DEFAULT NULL,
              `created_timestamp` TEXT DEFAULT NULL,
              `lastmod_wfd_user_id` INTEGER DEFAULT NULL,
              `lastmod_timestamp` TEXT DEFAULT NULL,
              `deleted_wfd_user_id` INTEGER DEFAULT NULL,
              `deleted_timestamp` TEXT DEFAULT NULL,
              `last_touched` TEXT DEFAULT NULL,
              `doc_descr` TEXT DEFAULT NULL,
              UNIQUE (`wfd_table_id`, `data_id`)
            );
        SQL);

        $connection->executeStatement(<<<SQL
            CREATE INDEX `idx_last_touched` ON `wfd_meta` (`last_touched`);
        SQL);
        $connection->executeStatement(<<<SQL
            CREATE INDEX `idx_touched_table` ON `wfd_meta` (`wfd_table_id`, `last_touched`);
        SQL);
        $connection->executeStatement(<<<SQL
            CREATE TABLE `wfd_table` (
              `id` smallint unsigned NOT NULL,
              `name` varchar(100) NOT NULL DEFAULT '',
              `tablename` varchar(100) NOT NULL DEFAULT '',
              `_comment` varchar(100) DEFAULT NULL,
              PRIMARY KEY (`id`)
            )
        SQL);
    }
}
