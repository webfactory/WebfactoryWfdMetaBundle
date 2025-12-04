<?php

/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use InvalidArgumentException;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Lock\LockFactory;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;
use Webfactory\Bundle\WfdMetaBundle\Util\CriticalSection;

/**
 * Custom ConfigCacheFactoryInterface implementation. Creates cache instances
 * that are able to track special resource types implementing `WfdMetaResource`.
 *
 * Works by decorating the default ConfigCache factory.
 */
class WfdMetaConfigCacheFactory implements ConfigCacheFactoryInterface
{
    /** @var ConfigCacheFactoryInterface */
    private $configCacheFactory;

    /** @var MetaQueryFactory */
    private $metaQueryFactory;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    private bool $expireWfdMetaResources;

    public function __construct(ConfigCacheFactoryInterface $configCacheFactory, MetaQueryFactory $metaQueryFactory, LockFactory $lockFactory, bool $expireWfdMetaResources = false)
    {
        $this->configCacheFactory = $configCacheFactory;
        $this->metaQueryFactory = $metaQueryFactory;
        $this->lockFactory = $lockFactory;
        $this->expireWfdMetaResources = $expireWfdMetaResources;
    }

    public function expireWfdMetaResources(): void
    {
        $this->expireWfdMetaResources = true;
    }

    public function cache($file, $callback): ConfigCacheInterface
    {
        if (!\is_callable($callback)) {
            throw new InvalidArgumentException(\sprintf('Invalid type for callback argument. Expected callable, but got "%s".', \gettype($callback)));
        }

        $wfdMetaCache = null;

        $innerCache = $this->configCacheFactory->cache($file, function (ConfigCacheInterface $innerCache) use ($file, $callback, &$wfdMetaCache) {
            $wfdMetaCache = $this->createCache($file, $innerCache);
            $this->fillCache($callback, $wfdMetaCache);
        });

        if ($wfdMetaCache) {
            // Cache has been refreshed since the "inner" cache was outdated
            return $wfdMetaCache;
        }

        // The "inner" cache was fresh. Now, wrap a WfdMetaConfigCache around it and validate the wfd_meta resources.
        $wfdMetaCache = $this->createCache($file, $innerCache);
        if (!$wfdMetaCache->isWfdMetaFresh()) {
            $this->fillCache($callback, $wfdMetaCache);
        }

        return $wfdMetaCache;
    }

    private function createCache($file, ConfigCacheInterface $innerCache): ConfigCacheInterface
    {
        return new WfdMetaConfigCache($file, $innerCache, $this->metaQueryFactory, $this->expireWfdMetaResources);
    }

    private function fillCache($callback, ConfigCacheInterface $cache): void
    {
        // Make sure only one process (on this host) will rebuild the cache, others wait for it
        $cs = new CriticalSection($this->lockFactory);
        $cs->execute($cache->getPath(), function () use ($callback, $cache) {
            if (!$cache->isFresh()) {
                // Our turn and the cache is still stale. Rebuild. */
                $callback($cache);
            } // else: Our turn, but cache is fresh. Must have been rebuilt while we were blocked. Use it.
        });
    }
}
