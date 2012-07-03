<?php

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\DBAL\Connection;

class Provider {

    protected $connection;
    protected $cache;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function getLastTouched($tables) {
        $hash = md5(json_encode($tables));

        if (!isset($this->cache[$hash])) {

            $ids = array();
            $names = array();
            foreach ((array)$tables as $t) {
                if (is_numeric($t))
                    $ids[] = $t;
                else
                    $names[] = $t;
            }

            $this->cache[$hash] = $this->connection->fetchColumn('
                    SELECT UNIX_TIMESTAMP(MAX(last_touched))
                    FROM wfd_meta m JOIN wfd_table t on m.wfd_table_id = t.id
                    WHERE '
                        . ($ids ? ('t.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')') : '')
                        . (($ids && $names) ? ' OR ' : '')
                        . ($names ? ('t.tablename IN (' . implode(', ', array_fill(0, count($names), '?')). ')') : '')
            , array_merge($ids, $names));
        }

        return $this->cache[$hash];
    }

}
