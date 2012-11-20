<?php

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use Symfony\Component\Config\Wakeup\ResourceWakeupInterface;
use Symfony\Component\Config\Resource\ResourceInterface;
use Webfactory\Bundle\WfdMetaBundle\Provider;

class ResourceWakeup implements ResourceWakeupInterface {

    protected $provider;

    public function __construct(Provider $provider) {
        $this->provider = $provider;
    }

    public function wakeup(ResourceInterface $resource) {
        if ($resource instanceof TableResource) {
            $resource->setProvider($this->provider);
        }
    }

}