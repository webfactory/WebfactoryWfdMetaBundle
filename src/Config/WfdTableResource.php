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
 * A resource tracking a particular database table. This resource is fresh until wfd_meta
 * tracks a change for at least one row in this table.
 */
class WfdTableResource implements ResourceInterface, WfdMetaResource
{
    /**
     * @var string
     */
    private $tablename;

    public function __construct($tablename)
    {
        $this->tablename = $tablename;
    }

    public function register(MetaQuery $query)
    {
        $query->addTable($this->tablename);
    }

    public function __toString()
    {
        return self::class.' '.$this->tablename;
    }

    /**
     * @deprecated, only present for BC with Symfony 2.x. Remove for Symfony 3.x.
     */
    public function isFresh($timestamp)
    {
    }

    /**
     * @deprecated, only present for BC with Symfony 2.x. Remove for Symfony 3.x.
     */
    public function getResource()
    {
    }
}
