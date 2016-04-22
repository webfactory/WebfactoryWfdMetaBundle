<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Util;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Stellt sicher, dass ein bestimmter Code-Abschnitt *von unterschiedlichen Prozessen
 * auf dem gleichen System* sequentiell durchlaufen wird. Braucht System V Semaphores
 * aus der PHP pcntl-Extension.
 *
 * Basis der Synchronisation ist eine Datei, die an execute() übergeben wird und die
 * existieren muss. Alle Prozesse, die CriticalSection auf Basis der gleichen Datei
 * ausführen, werden synchronisiert - unabhängig davon, was sie im callback
 * tun.
 *
 * CriticalSection ist re-entrant, d. h. wenn ein Prozess seinen callback zur Ausführung
 * bringt kann er für die gleiche Synchronisationsdatei weitere CriticalSection-Aufrufe
 * ausführen, die nicht blockieren werden.
 */
class CriticalSection
{

    protected static $entranceCount = array();
    protected static $semaphore = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setLogger(LoggerInterface $l)
    {
        $this->logger = $l;
    }

    public function execute($file, \Closure $callback)
    {
        $tok = ftok($file, 'x');

        self::lock($tok);

        $e = null;
        try {
            $r = $callback();
        } catch (\Exception $e) { /* fake finally {} */
        }

        self::release($tok);

        if ($e) {
            throw $e;
        }

        return $r;
    }

    protected function lock($tok)
    {
        if (!isset(self::$entranceCount[$tok]) || !self::$entranceCount[$tok]) {
            $this->debug("Waiting for the lock $tok");
            sem_acquire(self::$semaphore[$tok] = sem_get($tok));
            $this->debug("Obtained the lock $tok");
            self::$entranceCount[$tok] = 0;
        }

        self::$entranceCount[$tok]++;
    }

    protected function release($tok)
    {
        self::$entranceCount[$tok]--;

        if (!self::$entranceCount[$tok]) {
            $this->debug("Releasing the lock $tok");
            sem_release(self::$semaphore[$tok]);
        }
    }

    protected function debug($msg)
    {
        if ($this->logger) {
            $this->logger->debug($msg, array('pid' => getmypid()));
        }
    }

}
