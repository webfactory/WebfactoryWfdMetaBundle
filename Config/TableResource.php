<?php

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use Symfony\Component\Config\Resource\ResourceInterface;
use Webfactory\Bundle\WfdMetaBundle\Provider;

class TableResource implements ResourceInterface {

    protected $tableName;
    protected $provider;

    public function __construct($tableName) {
        $this->tableName = $tableName;
    }

    public function setProvider(Provider $provider) {
        $this->provider = $provider;
    }

    public function getResource() {
        return $this->tableName;
    }

    public function __toString() {
        return (string) $this->tableName;
    }

    public function isFresh($timestamp) {
        if ($this->provider)
            return $this->provider->getLastTouched(array($this->tableName)) < $timestamp;

        return false;
    }

    public function __sleep() {
        return array('tableName');
    }

}