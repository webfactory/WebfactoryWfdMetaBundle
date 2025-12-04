<?php

/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Util;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Finder\Finder;

/**
 * Verhaelt sich wie ConfigCache, ist aber zusätzlich und auch unter !debug nicht mehr fresh,
 * wenn der im Konstruktor gegebene Timestamp sich gegenüber dem Zeitpunkt der Erstellung
 * geändert hat (für die gleiche Cache-Datei).
 *
 * Diese Implementierung verwendet wechselnde Dateinamen für neuere Generationen,
 * weil so Cache-Datei (die letztlich ja von Klienten includiert wird) von APC gehalten
 * werden kann. In der Regel - unter Production und wenn der Cache frisch ist - müssen
 * wir dann auch nicht jedes Mal aus einer Meta-Datei laden, welche Generation im Cache
 * liegt und das für den isFresh()-Test vergleichen - der Dateiname alleine reicht
 * schon aus um zu erkennen, dass es die richtige Generation ist.
 */
class ExpirableConfigCache extends ConfigCache
{
    protected $timestamp;
    protected $baseFilename;
    protected $timestampedFile;

    public function __construct($baseFilename, $debug, $timestamp)
    {
        $this->baseFilename = $baseFilename;
        $this->timestampedFile = "{$baseFilename}_{$timestamp}";
        $this->timestamp = $timestamp;

        parent::__construct($this->timestampedFile, $debug);
    }

    public function write(string $content, ?array $metadata = null): void
    {
        parent::write($content, $metadata);

        /*
         * Für das Symfony2-Plugin in PHPStorm einen festen Dateinamen vorhalten,
         * diese Kopie ist ansonsten ohne Relevanz.
         */
        copy(
            $this->timestampedFile,
            $this->baseFilename
        );

        $this->cleanup();
    }

    protected function cleanup(): void
    {
        $finder = new Finder();
        $basename = basename($this->baseFilename);
        $files = $finder->files()
            ->in(\dirname($this->baseFilename))
            ->depth('== 0')
            ->name("#{$basename}_#")
            ->notName("#{$basename}_{$this->timestamp}#");

        foreach ($files as $file) {
            /* @var $file \SplFileInfo */
            @unlink($file->getRealPath());
        }
    }
}
