<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Util;

use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * Make sure that code - provided as a closure - is being run sequentially by different
 * operating-system level processes on this machine.
 *
 * Synchronization is based on a file name that has to be passed to `execute()`. All processes
 * that call this method providing the same file will be run in sequence - regardless of what
 * they do in the closure.
 *
 * The critical section is re-entrant. That means that once a process has entered the section and the closure
 * is being executed, this process can perform additional CriticalSection tasks (based on the same synchronization
 * file!), possibly using other callbacks, without being blocked.
 */
class CriticalSection
{
    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * List of active locks.
     *
     * The lock name is used as key.
     *
     * @var array<string, Lock>
     */
    private static $locks = [];

    /**
     * Counts how often a specific lock was requested.
     *
     * The lock name is used as key.
     *
     * @var array<string, int>
     */
    private static $entranceCount = [];

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * Sets a logger that is used to send debugging messages.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Blocks until no other process on this machine is executing a critical section
     * linked to the given file. Then, enter the critical section and execute the given callback.
     *
     * @param string $file File path that is used as lock name.
     *
     * @return mixed Return value of the callback.
     */
    public function execute($file, Closure $callback)
    {
        $this->lock($file);
        try {
            return $callback();
        } finally {
            $this->release($file);
        }
    }

    private function lock(string $lockName)
    {
        $this->debug("Requesting lock $lockName");
        if (!$this->getLock($lockName)->acquire(true)) {
            $this->debug("Failed to get lock $lockName");
            throw new RuntimeException("Failed to get lock $lockName");
        }
        if (!isset(self::$entranceCount[$lockName])) {
            self::$entranceCount[$lockName] = 0;
        }
        ++self::$entranceCount[$lockName];
        $this->debug("Obtained the lock $lockName");
    }

    private function release(string $lockName)
    {
        --self::$entranceCount[$lockName];
        if (0 === self::$entranceCount[$lockName]) {
            $this->debug("Releasing the lock $lockName");
            $this->getLock($lockName)->release();
        }
    }

    /**
     * Returns the lock with the provided name.
     *
     * A new lock object will be created if it does not exist yet.
     * This method will *not* automatically acquire the lock.
     */
    private function getLock(string $name): LockInterface
    {
        if (!isset(self::$locks[$name])) {
            $lock = $this->lockFactory->createLock($name);
            self::$locks[$name] = $lock;
        }

        return self::$locks[$name];
    }

    /**
     * Logs the given message if a logger is available.
     */
    private function debug(string $message): void
    {
        if ($this->logger) {
            $this->logger->debug($message, ['pid' => getmypid()]);
        }
    }
}
