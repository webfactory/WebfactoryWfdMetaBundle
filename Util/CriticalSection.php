<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Util;

use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Stellt sicher, dass ein bestimmter Code-Abschnitt *von unterschiedlichen Prozessen
 * auf dem gleichen System* sequentiell durchlaufen wird.
 *
 * Basis der Synchronisation ist eine Datei, die an execute() übergeben wird. Alle
 * Prozesse, die CriticalSection auf Basis der gleichen Datei ausführen, werden
 * synchronisiert - unabhängig davon, was sie im callback tun.
 *
 * CriticalSection ist re-entrant, d. h. wenn ein Prozess seinen callback zur Ausführung
 * bringt kann er für die gleiche Synchronisationsdatei weitere CriticalSection-Aufrufe
 * ausführen, die nicht blockieren werden.
 */
class CriticalSection
{
    /**
     * List of active locks.
     *
     * The lock name is used as key.
     *
     * @var array<string, LockHandler>
     */
    protected static $locks = array();

    /**
     * Counts how often a specific lock was requested.
     *
     * The lock name is used as key.
     *
     * @var array<string, integer>
     */
    protected static $entranceCount = array();

    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * Sets a logger that is used to send debugging messages.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $file File path that is used as lock name.
     * @param \Closure $callback
     * @return mixed Return value of the callback.
     */
    public function execute($file, \Closure $callback)
    {
        self::lock($file);
        try {
            return $callback();
        } finally {
            self::release($file);
        }
    }

    /**
     * @param string $lockName
     */
    protected function lock($lockName)
    {
        $this->debug("Requesting lock $lockName");
        if (!$this->getLock($lockName)->lock(true)) {
            $this->debug("Failed to get lock $lockName");
            throw new \RuntimeException("Failed to get lock $lockName");
        }
        if (!isset(self::$entranceCount[$lockName])) {
            self::$entranceCount[$lockName] = 0;
        }
        self::$entranceCount[$lockName]++;
        $this->debug("Obtained the lock $lockName");
    }

    /**
     * @param string $lockName
     */
    protected function release($lockName)
    {
        self::$entranceCount[$lockName]--;
        if (self::$entranceCount[$lockName] === 0) {
            $this->debug("Releasing the lock $lockName");
            $this->getLock($lockName)->release();
        }
    }

    /**
     * Returns the lock with the provided name.
     *
     * A new lock object will be created if it does not exist yet.
     * This method will *not* automatically acquire the lock.
     *
     * @param string $name
     * @return LockHandler
     */
    protected function getLock($name)
    {
        if (!isset(self::$locks[$name])) {
            self::$locks[$name] = new LockHandler($name);
        }
        return self::$locks[$name];
    }

    /**
     * Logs the given message if a logger is available.
     *
     * @param string $message
     */
    protected function debug($message)
    {
        if ($this->logger) {
            $this->logger->debug($message, array('pid' => getmypid()));
        }
    }
}
