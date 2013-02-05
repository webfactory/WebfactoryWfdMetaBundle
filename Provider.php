<?php

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\DBAL\Connection;

class Provider {

    protected $connection;
    protected $cache = array();

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function getLastTouched($tables) {
        if($noneCached = $this->findNoneCached($tables)) {
            $this->cache = array_merge(
                $this->cache,
                $this->fetchNoneCached($noneCached)
            );
        }
        
        return $this->findLastestTimestamp($tables);
    }
    
    protected function findNoneCached($tables) {
        $noneCached = array();
        foreach ($tables as $table) {
            if(!isset($this->cache[$table])) {
                $noneCached[] = $table;
            }
        }
        return $noneCached;
    }
    
    protected function fetchNoneCached($noneCached) {
        $ids = array();
        $names = array();
        foreach ((array)$noneCached as $t) {
            if (is_numeric($t))
                $ids[] = $t;
            else
                $names[] = $t;
        }

        $result = $this->connection->fetchAll('
            SELECT t.id, t.tablename, UNIX_TIMESTAMP(MAX(m.last_touched)) timestamp
            FROM wfd_meta m 
            JOIN wfd_table t on m.wfd_table_id = t.id
            WHERE '
                . ($ids ? ('t.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')') : '')
                . (($ids && $names) ? ' OR ' : '')
                . ($names ? ('t.tablename IN (' . implode(', ', array_fill(0, count($names), '?')). ')') : '') .'
            GROUP BY t.tablename, t.id'
        , array_merge($ids, $names));
        
        $tables = array();
        foreach($noneCached as $t) {
            foreach($result as $table) {
                if(($table['tablename'] == $t) || ($table['id'] == $t)) {
                    $tables[$t] = $table['timestamp'];
                }
            }
        }
        
        return $tables;
    }
    
    protected function findLastestTimestamp($tables) {
        $lastestTimestamp = 0;
        foreach($tables as $table) {
            $timestamp = $this->cache[$table];
            if ($lastestTimestamp < $timestamp) {
                $lastestTimestamp = $timestamp;
            }
        }
        return $lastestTimestamp;
    }

}
