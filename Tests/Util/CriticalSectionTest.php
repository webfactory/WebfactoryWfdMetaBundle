<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Webfactory\Bundle\WfdMetaBundle\Util\CriticalSection;

class CriticalSectionTest extends TestCase
{
    /**
     * System under test.
     *
     * @var CriticalSection
     */
    private $criticalSection;

    /**
     * Initializes the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $lockFactory = new LockFactory(new FlockStore());
        $this->criticalSection = new CriticalSection($lockFactory);
    }

    /**
     * Cleans up the test environment.
     */
    protected function tearDown(): void
    {
        $this->criticalSection = null;
        parent::tearDown();
    }

    public function testExecuteReturnsValueFromCallback()
    {
        $result = $this->criticalSection->execute(__DIR__.'/my/virtual/file', function () {
            return 42;
        });

        $this->assertEquals(42, $result);
    }

    public function testInvokesCallbacksWithDifferentLocks()
    {
        $invoked = false;

        $this->criticalSection->execute(__DIR__.'/my/virtual/file1', function () use (&$invoked) {
            $invoked = true;
        });
        $this->criticalSection->execute(__DIR__.'/my/virtual/file2', function () use (&$invoked) {
            $invoked = true;
        });

        self::assertTrue($invoked);
    }

    public function testInvokesCallbackWithSameLock()
    {
        $invoked = false;

        $this->criticalSection->execute(__DIR__.'/my/virtual/file1', function () use (&$invoked) {
            $invoked = true;
        });
        $this->criticalSection->execute(__DIR__.'/my/virtual/file1', function () use (&$invoked) {
            $invoked = true;
        });

        self::assertTrue($invoked);
    }

    /**
     *  This ensures that the critical section is re-entrant as documented.
     */
    public function testCallbackCanAcquireSameLockAgain()
    {
        $invoked = false;

        $this->criticalSection->execute(__DIR__.'/my/virtual/file1', function () use (&$invoked) {
            $this->criticalSection->execute(__DIR__.'/my/virtual/file1', function () use (&$invoked) {
                $invoked = true;
            });
        });

        self::assertTrue($invoked);
    }
}
