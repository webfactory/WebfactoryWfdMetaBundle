<?php

namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

/**
 * @Annotation
 * @Deprecated
 */
class ValidUntilLastTouched extends Send304IfNotModified {
    public function __construct($values)
    {
        trigger_error(
            'The ValidUntilLastTouched annotation is deprecated. Use Send304IfNotModified instead.',
            E_USER_DEPRECATED
        );

        parent::__construct($values);
    }
}
