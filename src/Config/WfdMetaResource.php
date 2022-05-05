<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use Webfactory\Bundle\WfdMetaBundle\MetaQuery;

/**
 * A resource that can register itself with a MetaQuery.
 */
interface WfdMetaResource
{
    /**
     * @return void
     */
    public function register(MetaQuery $query);
}
