<?php

namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

/**
 * @Annotation
 */
class ValidUntilLastTouched {

    protected $tables = array();

    public function __construct($values) {
        foreach ($values as $key => $value) {
            if (method_exists($this, $name = 'set' . ucfirst($key))) {
                $this->$name($value);
            } else {
                throw new \Exception('Die Annotation ' . get_class($this) . ' kennt die Eigentschaft "' . $key . '" nicht.');
            }
        }
    }

    public function setTableIdConstants($tableIdConstants) {
        $this->tables = array_merge($this->tables, array_map(function ($x) { return constant($x); }, $tableIdConstants));
    }

    public function setTables($tables) {
        $this->tables = array_merge($this->tables, $tables);
    }

    public function getTables() {
        return $this->tables;
    }

}
