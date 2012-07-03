<?php

namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

/**
 * @Annotation
 */
class ValidUntilLastTouched {

    protected $tableIdConstants;

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
        $this->tableIdConstants = $tableIdConstants;
    }

    public function getTableIdConstants() {
        return $this->tableIdConstants;
    }

}