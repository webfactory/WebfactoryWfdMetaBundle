<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle;

use Doctrine\DBAL\Connection;

/**
 * Encapsulates the wfd_meta table. Use it to query the timestamp of the last change (change
 * or deletion) in one or several tables identified by their table names or wfDynamic table IDs.
 *
 * Can also be used to query this information for a single record (row in a particular table).
 */
class Provider
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the unix timestamp of the last change affecting one of the tables, given as database table names or
     * wfDynamic table IDs.
     *
     * @param array $tables Table names or IDs
     *
     * @return int|null The Unix timestamp for the last change; null if the information is not available
     */
    public function getLastTouched(array $tableNamesOrIds)
    {
        if (!$tableNamesOrIds) {
            return 0;
        }

        $ids = array();
        $names = array();

        foreach ($tableNamesOrIds as $t) {
            if ($t == '*') {
                $timestamp = $this->connection->fetchColumn(
                    'SELECT UNIX_TIMESTAMP(MAX(last_touched)) FROM wfd_meta'
                );

                return $timestamp;
            } elseif (is_numeric($t)) {
                $ids[] = $t;
            } else {
                $names[] = $t;
            }
        }

        if ($names || $ids) {
            $timestamp = $this->connection->fetchColumn('
                SELECT UNIX_TIMESTAMP(MAX(m.last_touched)) 
                FROM wfd_meta m
                JOIN wfd_table t on m.wfd_table_id = t.id
                WHERE '
                . ($ids ? ('t.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')') : '')
                . (($ids && $names) ? ' OR ' : '')
                . ($names ? ('t.tablename IN (' . implode(', ', array_fill(0, count($names), '?')) . ')') : ''),
                array_merge($ids, $names)
            );

            return $timestamp;
        }
    }

    /**
     * Returns the Unix timestamp for the last change of a single row in a given table.
     *
     * @param string $tablename  The table containing the data row in question
     * @param int    $primaryKey The data-id of the row in question
     *
     * @return int|null The Unix timestamp for the last change of the given row; null if the information is not available
     */
    public function getLastTouchedRow($tablename, $primaryKey)
    {
        $lastTouched = $this->connection->fetchColumn('
            SELECT m.last_touched
            FROM wfd_meta m
            JOIN wfd_table t on m.wfd_table_id = t.id
            WHERE t.tablename = ? AND m.data_id = ?',
            [$tablename, $primaryKey]
        );

        if ($lastTouched === false) {
            return null;
        }

        $lastTouchedObject = new \DateTime($lastTouched);
        return $lastTouchedObject->getTimestamp();
    }
}
