<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Caching\Attribute;

use Attribute;
use Exception;
use InvalidArgumentException;
use Webfactory\Bundle\WfdMetaBundle\Helper\LastmodHelper;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

/**
 * @deprecated Use WebfactoryHttpCachingBundle and its LastModifiedDeterminators instead. If in a hurry, @see \Webfactory\Bundle\WfdMetaBundle\Caching\WfdMetaQueries for a quick conversion.
 */
#[Attribute]
class Send304IfNotModified
{
    protected $lastmodHelper;

    public function __construct(...$values)
    {
        @trigger_error(
            'The Send304IfNotModified attribute is deprecated. Use WebfactoryHttpCachingBundle and its LastModifiedDeterminators instead. If in a hurry, @see \Webfactory\Bundle\WfdMetaBundle\Caching\WfdMetaQueries for a quick conversion.',
            \E_USER_DEPRECATED
        );

        if (!$values) {
            throw new InvalidArgumentException(\sprintf('The %s attribute needs at least one criterion', __CLASS__));
        }

        $this->lastmodHelper = new LastmodHelper();

        foreach ($values as $key => $value) {
            if (method_exists($this->lastmodHelper, $name = 'set'.ucfirst($key))) {
                $this->lastmodHelper->$name($value);
            } else {
                throw new Exception('Die Annotation '.static::class.' kann die Eigentschaft "'.$key.'" nicht setzen.');
            }
        }
    }

    public function calculateLastModified(MetaQueryFactory $metaQueryFactory)
    {
        return $this->lastmodHelper->calculateLastModified($metaQueryFactory);
    }
}
