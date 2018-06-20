<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Caching;

use Symfony\Component\HttpFoundation\Request;
use Webfactory\Bundle\WfdMetaBundle\Helper\LastmodHelper;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;
use Webfactory\HttpCacheBundle\NotModified\LastModifiedDeterminator;

/**
 * This class can be used to quickly convert an old Send304IfNotModified annotation of the WfdMetaBundle to a
 * LastModifiedDeterminator used in the WebfactoryHttpCachingBundle. This does not fully embrace the concept of
 * LastModifiedDeterminators (especially not when using resetInterval; see the bundle's readme), but if you're in a
 * hurry, maybe you don't want to be bothered with that.
 *
 * @ Send304IfNotModified(
 *     tables = {"*", "tablename", "42", ...},
 *     entities = {"AcmeBundle:BlogPost"},
 *     tableIdConstants = {"MEDIA_TABLE_ID"},
 *     resetInterval = 3600
 * )
 *
 * becomes (without the spaces behind the "@"s):
 *
 * @ ReplaceWithNotModifiedResponse({"@ app_caching_mycontroller_myaction"})
 *
 * with the following service definition:
 *
 * <service id="app_caching_mycontroller_myaction" class="Webfactory\Bundle\WfdMetaBundle\Caching\WfdMetaQueries">
 *     <argument type="service" id="webfactory_wfd_meta.meta_query_factory" />
 *     <argument type="collection">
 *         <argument key="tables" type="collection">
 *             <argument type="string">*</argument>
 *             <argument type="string">tablename</argument>
 *             <argument type="string">42</argument>
 *         </argument>
 *         <argument key="entities" type="collection">
 *             <argument type="string">AcmeBundle:BlogPost</argument>
 *         </argument>
 *         <argument key="tableIdConstants" type="collection">
 *             <argument type="string">MEDIA_TABLE_ID</argument>
 *         </argument>
 *         <argument key="resetInterval" type="string">3600</argument>
 *     </argument>
 * </service>
 */
final class WfdMetaQueries implements LastModifiedDeterminator
{
    /** @var LastmodHelper */
    private $lastmodHelper;

    /** @var MetaQueryFactory */
    private $metaQueryFactory;

    public function __construct(MetaQueryFactory $metaQueryFactory, array $parameters)
    {
        $this->lastmodHelper = new LastmodHelper();
        $this->metaQueryFactory = $metaQueryFactory;

        foreach ($parameters as $key => $value) {
            if (method_exists($this->lastmodHelper, $name = 'set'.ucfirst($key))) {
                $this->lastmodHelper->$name($value);
            } else {
                throw new \Exception('Die Annotation '.get_class($this).' kann die Eigentschaft "'.$key.'" nicht setzen.');
            }
        }
    }

    public function getLastModified(Request $request)
    {
        return $this->lastmodHelper->calculateLastModified($this->metaQueryFactory);
    }
}
