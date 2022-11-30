<?php

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\ResourceCheckerInterface;

class CacheBustingResourceChecker implements ResourceCheckerInterface
{
    public function supports(ResourceInterface $metadata): bool
    {
        return $metadata instanceof WfdMetaResource;
    }

    public function isFresh(ResourceInterface $resource, int $timestamp): bool
    {
        return false;
    }
}
