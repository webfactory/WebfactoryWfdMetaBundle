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
     * Returns the last UNIX timestamp of any change on any of the given tables or 0, if no matching entry was found.
     *
     * @param array $tables The table names or table ids to check for changes.
     * @return int|null UNIX timestamp or null if no entries were found.
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
                $lastTouchOnAnyTable = $this->connection->fetchAssoc('SELECT MAX(last_touched) lastTouchedString FROM wfd_meta');
                if ($lastTouchOnAnyTable['lastTouchedString'] === null) {
                    return null;
                }

                $lastTouchedObject = new \DateTime($lastTouchOnAnyTable['lastTouchedString']);
                return $lastTouchedObject->getTimestamp();
            } elseif (is_numeric($t)) {
                $ids[] = $t;
            } else {
                $names[] = $t;
            }
        }

        if ($names || $ids) {
            $lastTouched = $this->connection->fetchColumn('
                SELECT MAX(m.last_touched) lastTouchedString
                FROM wfd_meta m
                JOIN wfd_table t on m.wfd_table_id = t.id
                WHERE '
                . ($ids ? ('t.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')') : '')
                . (($ids && $names) ? ' OR ' : '')
                . ($names ? ('t.tablename IN (' . implode(', ', array_fill(0, count($names), '?')) . ')') : ''),
                array_merge($ids, $names)
            );

            if ($lastTouched === null) {
                return null;
            }

            $lastTouchedObject = new \DateTime($lastTouched);
            return $lastTouchedObject->getTimestamp();
        }
    }

    /**
     * Returns all tracked data rows and their respective last changes of a given table.
     *
     * @param string $tableName
     * @return array
     */
    public function getLastTouchedOfEachRow($tableName)
    {
        $lastTouchedData = $this->connection->fetchAll('
            SELECT m.data_id, m.last_touched
            FROM wfd_meta m
            JOIN wfd_table t on m.wfd_table_id = t.id
            WHERE t.tablename = ?',
            [$tableName]
        );

        $idAndVersionParis = [];
        foreach ($lastTouchedData as $row) {
            $lastTouchedObject = new \DateTime($row['last_touched']);
            $idAndVersionParis[$row['data_id']] = $lastTouchedObject->getTimestamp();
        }

        return $idAndVersionParis;
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

    protected function cache(array $namesOrIds)
    {
        $ids = array();
        $names = array();

        foreach ($namesOrIds as $t) {
            $this->cache[$t] = 0; // prevent re-query
            if ($t == '*') {
                $lastTouchOnAnyTable = $this->connection->fetchAssoc('SELECT MAX(last_touched) lastTouchedString FROM wfd_meta');

                if ($lastTouchOnAnyTable['lastTouchedString'] === false) {
                    $this->cache['*'] = null;
                } else {
                    $lastTouchedObject = new \DateTime($lastTouchOnAnyTable['lastTouchedString']);
                    $this->cache['*'] = $lastTouchedObject->getTimestamp();
                }
            } elseif (is_numeric($t)) {
                $ids[] = $t;
            } else {
                $names[] = $t;
            }
        }

        if ($names || $ids) {
            $result = $this->connection->fetchAll('
                    SELECT t.id, t.tablename, MAX(m.last_touched) lastTouchedString
                    FROM wfd_meta m
                    JOIN wfd_table t on m.wfd_table_id = t.id
                    WHERE '
                    .($ids ? ('t.id IN ('.implode(', ', array_fill(0, count($ids), '?')).')') : '')
                    .(($ids && $names) ? ' OR ' : '')
                    .($names ? ('t.tablename IN ('.implode(', ', array_fill(0, count($names), '?')).')') : '').'
                    GROUP BY t.id
                ',
                array_merge($ids, $names)
            );

            foreach ($result as $row) {
                $lastTouchedObject = new \DateTime($row['lastTouchedString']);
                $this->cache[$row['id']] = $this->cache[$row['tablename']] = $lastTouchedObject->getTimestamp();
            }
        }
    }
}
