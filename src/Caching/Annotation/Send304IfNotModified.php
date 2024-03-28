<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

/**
 * @Annotation
 *
 * @deprecated Use the \Webfactory\Bundle\WfdMetaBundle\Caching\Attribute\Send304IfNotModified attribute instead
 */
class Send304IfNotModified extends \Webfactory\Bundle\WfdMetaBundle\Caching\Attribute\Send304IfNotModified
{
    public function __construct($values)
    {
        @trigger_error(sprintf('The %s annotation is deprecated, use the %s attribute instead', __CLASS__, \Webfactory\Bundle\WfdMetaBundle\Caching\Attribute\Send304IfNotModified::class), \E_USER_DEPRECATED);
        parent::__construct(...$values);
    }
}
