<?php

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\DBAL\Connection;

class Provider {

    protected $connection;
    protected $cache = array();

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function getLastTouched(array $tables) {
        $flip = array_flip($tables);

        if ($cacheMiss = array_diff_key($flip, $this->cache)) {
            $this->cache(array_keys($cacheMiss));
        }

        return max(array_intersect_key($this->cache, $flip));
    }

    protected function cache(array $namesOrIds) {
        $ids = array();
        $names = array();

        foreach ($namesOrIds as $t) {
            $this->cache[$t] = 0; // prevent re-query
            if ($t == '*') {
                $lastTouchOnAnyTable = $this->connection->fetchAssoc(
                    'SELECT UNIX_TIMESTAMP(MAX(last_touched)) timestamp FROM wfd_meta'
                );
                $this->cache['*'] = $lastTouchOnAnyTable['timestamp'];
            } elseif (is_numeric($t)) {
                $ids[] = $t;
            } else {
                $names[] = $t;
            }
        }

        if ($names ||$ids) {
            $result = $this->connection->fetchAll('
                SELECT t.id, t.tablename, UNIX_TIMESTAMP(MAX(m.last_touched)) timestamp
                FROM wfd_meta m
                JOIN wfd_table t on m.wfd_table_id = t.id
                WHERE '
                    . ($ids ? ('t.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')') : '')
                    . (($ids && $names) ? ' OR ' : '')
                    . ($names ? ('t.tablename IN (' . implode(', ', array_fill(0, count($names), '?')). ')') : '') .'
                GROUP BY t.id'
            , array_merge($ids, $names));

            foreach ($result as $row) {
                $this->cache[$row['id']] = $this->cache[$row['tablename']] = $row['timestamp'];
            }
        }
    }

}
