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
 * @Deprecated
 */
class ValidUntilLastTouched extends Send304IfNotModified
{
    public function __construct($values)
    {
        @trigger_error(
            'The ValidUntilLastTouched annotation is deprecated. Use Send304IfNotModified instead.',
            \E_USER_DEPRECATED
        );

        parent::__construct($values);
    }
}
