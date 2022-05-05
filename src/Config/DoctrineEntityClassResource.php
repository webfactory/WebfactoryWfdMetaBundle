<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use Symfony\Component\Config\Resource\ResourceInterface;
use Webfactory\Bundle\WfdMetaBundle\MetaQuery;

/**
 * A resource tracking a particular Doctrine entity class. This resource is fresh until wfd_meta
 * tracks a change for at least one entity instance.
 */
class DoctrineEntityClassResource implements ResourceInterface, WfdMetaResource
{
    private $classname;

    public function __construct($classname)
    {
        $this->classname = $classname;
    }

    public function register(MetaQuery $query): void
    {
        $query->addEntityClass($this->classname);
    }

    public function __toString(): string
    {
        return self::class.' '.$this->classname;
    }
}
