<?php

namespace Webfactory\Bundle\WfdMetaBundle\Util;

use Symfony\Component\Config\ConfigCache;

/**
 * Verhaelt sich wie ConfigCache, ist aber zusätzlich und auch unter !debug nicht mehr fresh,
 * wenn der im Konstruktor gegebene Timestamp sich gegenüber dem Zeitpunkt der Erstellung
 * erhöht hat (für die gleiche Cache-Datei).
 *
 * Diese Implementierung verwendet wechselnde Dateinamen für neuere Generationen,
 * weil so Cache-Datei (die letztlich ja von Klienten includiert wird) von APC gehalten
 * werden kann. In der Regel - unter Production und wenn der Cache frisch ist - müssen
 * wir dann auch nicht jedes Mal aus einer Meta-Datei laden, welche Generation im Cache
 * liegt und das für den isFresh()-Test vergleichen - der Dateiname alleine reicht
 * schon aus um zu erkennen, dass es die richtige Generation ist.
 */
class ExpirableConfigCache extends ConfigCache {
    protected $timestamp, $metaFile, $baseFile;

    public function __construct($file, $debug, $timestamp) {
        parent::__construct($this->getTimestampedFilePath($file, $timestamp), $debug);
        $this->baseFile = $file;
        $this->metaFile = "$file.expire";
        $this->timestamp = $timestamp;
    }

    public function write($content, array $metadata = null) {
        $file = $this->metaFile;
        $oldTs = @file_get_contents($file);

        if ($oldTs <= $this->timestamp) { // <=, weil es sein kann, dass wir den Cache aufgrund anderer Faktoren als dem Timestamp neu machen (think development + geänderte Resourcen)
            parent::write($content, $metadata);
            copy(
                $this->getTimestampedFilePath($this->baseFile, $this->timestamp),
                $this->baseFile
            );
        }

        if ($oldTs < $this->timestamp) { // Wenn wir keine echt neue Generation geschrieben haben, brauchen|dürfen wir das nicht neu schreiben|löschen.
            $tmpFile = tempnam(dirname($file), basename($file));
            if (false !== @file_put_contents($tmpFile, $this->timestamp) && @rename($tmpFile, $file)) {
                chmod($file, 0666);
                @unlink("{$this->baseFile}_$oldTs");
                @unlink("{$this->baseFile}_$oldTs.meta");
            }
        }
    }

    /**
     * Get the full file system path including the file name for a timestamped version of the $baseFilePath
     *
     * @param string $baseFilePath full file system path to the base file
     * @param int $timestamp
     * @return string
     */
    protected function getTimestampedFilePath($baseFilePath, $timestamp)
    {
        return $baseFilePath . '_' . $timestamp;
    }
}
