<?php


namespace Webfactory\Bundle\WfdMetaBundle\Caching\Annotation;

use Webfactory\Bundle\WfdMetaBundle\Helper\LastmodHelper;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

/**
 * @Annotation
 */
class Send304IfNotModified
{

    protected $lastmodHelper;

    public function __construct($values)
    {
        $this->lastmodHelper = new LastmodHelper();

        foreach ($values as $key => $value) {
            if (method_exists($this->lastmodHelper, $name = 'set' . ucfirst($key))) {
                $this->lastmodHelper->$name($value);
            } else {
                throw new \Exception('Die Annotation ' . get_class($this) . ' kann die Eigentschaft "' . $key . '" nicht setzen.');
            }
        }
    }

    public function calculateLastModified(MetaQueryFactory $metaQueryFactory)
    {
        return $this->lastmodHelper->calculateLastModified($metaQueryFactory);
    }

}
