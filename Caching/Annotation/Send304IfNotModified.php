<?php


namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

use Webfactory\Bundle\WfdMetaBundle\MetaQuery;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

/**
 * @Annotation
 */
class Send304IfNotModified {

    protected $tables;
    protected $tableIdConstants;
    protected $entities;
    protected $resetInterval = 2419200; // Default: 28 Tage

    public function __construct($values) {
        foreach ($values as $key => $value) {
            if (method_exists($this, $name = 'set' . ucfirst($key))) {
                $this->$name($value);
            } else {
                throw new \Exception('Die Annotation ' . get_class($this) . ' kennt die Eigentschaft "' . $key . '" nicht.');
            }
        }
    }

    public function calculateLastModified(MetaQueryFactory $metaQueryFactory) {
        $metaQuery = $metaQueryFactory->create();
        $this->configure($metaQuery);

        if ($lastTouched = $metaQuery->getLastTouched()) {
            $now = time();
            $age = $now - $lastTouched;

            $ts = $now - ($age % $this->resetInterval);

            return new \DateTime("@$ts");
        }

        return null;
    }

    protected function configure(MetaQuery $metaQuery) {
        try {

            if (!$this->tables && !$this->tableIdConstants && !$this->entities) {
                throw new \RuntimeException('Die Annotation ' . get_class($this) . ' wurde weder mit Tabellennamen, Tabellen-Ids oder Entitätsnamen konfiguriert.');
            }

            if ($this->tables) {
                $metaQuery->addTable($this->tables);
            }

            if ($this->tableIdConstants) {
                $metaQuery->addTable(
                    array_map(
                        function ($x) {
                            return constant($x);
                        },
                        $this->tableIdConstants
                    )
                );
            }

            if ($this->entities) {
                $metaQuery->addEntity($this->entities);
            }

        } catch (\Exception $e) {
            throw new \RuntimeException("Exception während der Konfiguration von MetaQuery durch die Annotation " . get_class($this), 0, $e);
        }
    }

    public function setResetInterval($resetInterval) {
        $this->resetInterval = $resetInterval;
    }

    public function setEntities($entities) {
        $this->entities = $entities;
    }

    public function setTableIdConstants($tableIdConstants) {
        $this->tableIdConstants = $tableIdConstants;
    }

    public function setTables($tables) {
        $this->tables = $tables;
    }

}
