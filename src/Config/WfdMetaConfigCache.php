<?php

/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Config;

use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

class WfdMetaConfigCache implements ConfigCacheInterface
{
    /** @var MetaQueryFactory */
    private $metaQueryFactory;

    private $file;

    /** @var ConfigCacheInterface */
    private $innerCache;

    private bool $forceExpiration;

    public function __construct($file, ConfigCacheInterface $innerCache, MetaQueryFactory $metaQueryFactory, bool $forceExpiration = false)
    {
        $this->file = $file;
        $this->innerCache = $innerCache;
        $this->metaQueryFactory = $metaQueryFactory;
        $this->forceExpiration = $forceExpiration;
    }

    public function getPath(): string
    {
        return $this->innerCache->getPath();
    }

    public function isFresh(): bool
    {
        if (!$this->innerCache->isFresh()) {
            return false;
        }

        if (!$this->isWfdMetaFresh()) {
            return false;
        }

        return true;
    }

    public function isWfdMetaFresh(): bool
    {
        $wfdMetaFile = $this->file.'.wfd_meta';

        if (!is_file($wfdMetaFile)) {
            return false;
        }

        $wfdMetaResources = unserialize(file_get_contents($wfdMetaFile));

        if (!$wfdMetaResources['resources']) {
            return true;
        }

        if ($this->forceExpiration) {
            return false;
        }

        $metaQuery = $this->metaQueryFactory->create();

        foreach ($wfdMetaResources['resources'] as $wfdMetaResource) {
            $wfdMetaResource->register($metaQuery);
        }

        return $metaQuery->getLastTouched() === $wfdMetaResources['timestamp'];
    }

    public function write($content, ?array $metadata = null): void
    {
        $this->innerCache->write($content, $metadata);

        // Create an extra cache layer by collecting all WfdMetaResources, fetching the current
        // timestamp for the data they represent and put everything together into an extra
        // `.wfd_meta` metadata file.

        $wfdMetaResources = [];

        foreach ($metadata as $key => $resource) {
            if ($resource instanceof WfdMetaResource) {
                $wfdMetaResources[(string) $resource] = $resource; // use key to dedup resource
            }
        }

        $timestamp = null;

        if ($wfdMetaResources) {
            $metaQuery = $this->metaQueryFactory->create();
            foreach ($wfdMetaResources as $wfdMetaResource) {
                $wfdMetaResource->register($metaQuery);
            }
            $timestamp = $metaQuery->getLastTouched();
        }

        $this->dumpWfdMetaFile(serialize(['resources' => array_values($wfdMetaResources), 'timestamp' => $timestamp]));
    }

    private function dumpWfdMetaFile($content): void
    {
        $mode = 0666;
        $umask = umask();
        $filesystem = new Filesystem();

        $filename = $this->file.'.wfd_meta';

        $filesystem->dumpFile($filename, $content);

        try {
            $filesystem->chmod($filename, $mode, $umask);
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }
    }
}
