<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Helper;

use DateTime;
use Exception;
use RuntimeException;
use Webfactory\Bundle\WfdMetaBundle\MetaQuery;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

class LastmodHelper
{
    protected $tables;
    protected $tableIdConstants;
    protected $entities;
    protected $resetInterval = 2419200; // Default: 28 Tage

    public function calculateLastModified(MetaQueryFactory $metaQueryFactory)
    {
        $metaQuery = $metaQueryFactory->create();
        $this->configure($metaQuery);

        if ($lastTouched = $metaQuery->getLastTouched()) {
            $now = time();
            $age = $now - $lastTouched;

            $ts = $now - ($age % $this->resetInterval);

            return new DateTime("@$ts");
        }

        return null;
    }

    protected function configure(MetaQuery $metaQuery)
    {
        try {
            if (!$this->tables && !$this->tableIdConstants && !$this->entities) {
                throw new RuntimeException(static::class.' wurde weder mit Tabellennamen, Tabellen-Ids oder Entitätsnamen konfiguriert.');
            }

            if ($this->tables) {
                $metaQuery->addTable($this->tables);
            }

            if ($this->tableIdConstants) {
                $metaQuery->addTable(
                    array_map(
                        'constant',
                        $this->tableIdConstants
                    )
                );
            }

            if ($this->entities) {
                $metaQuery->addEntityClasses($this->entities);
            }
        } catch (Exception $e) {
            throw new RuntimeException('Exception während der Konfiguration von MetaQuery durch '.static::class, 0, $e);
        }
    }

    public function setResetInterval($resetInterval)
    {
        $this->resetInterval = $resetInterval;
    }

    public function setEntities($entities)
    {
        $this->entities = $entities;
    }

    public function setTableIdConstants($tableIdConstants)
    {
        $this->tableIdConstants = $tableIdConstants;
    }

    public function setTables($tables)
    {
        $this->tables = $tables;
    }
}
