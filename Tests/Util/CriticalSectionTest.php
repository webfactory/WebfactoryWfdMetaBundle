<?php

namespace Webfactory\Bundle\WfdMetaBundle\Tests\Util;

use Webfactory\Bundle\WfdMetaBundle\Util\CriticalSection;

class CriticalSectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System under test.
     *
     * @var CriticalSection
     */
    private $criticalSection = null;

    /**
     * Initializes the test environment.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->criticalSection = new CriticalSection();
    }

    /**
     * Cleans up the test environment.
     */
    protected function tearDown()
    {
        $this->criticalSection = null;
        parent::tearDown();
    }

    public function testExecuteReturnsValueFromCallback()
    {
        $result = $this->criticalSection->execute(__DIR__ . '/my/virtual/file', function () {
            return 42;
        });

        $this->assertEquals(42, $result);
    }

    public function testInvokesCallbacksWithDifferentLocks()
    {
        $this->criticalSection->execute(__DIR__ . '/my/virtual/file1', $this->createCallbackThatMustBeInvoked());
        $this->criticalSection->execute(__DIR__ . '/my/virtual/file2', $this->createCallbackThatMustBeInvoked());
    }

    public function testInvokesCallbackWithSameLock()
    {
        $this->criticalSection->execute(__DIR__ . '/my/virtual/file1', $this->createCallbackThatMustBeInvoked());
        $this->criticalSection->execute(__DIR__ . '/my/virtual/file1', $this->createCallbackThatMustBeInvoked());
    }

    public function testCallbackCanAcquireSameLockAgain()
    {
        $this->criticalSection->execute(__DIR__ . '/my/virtual/file1', function () {
            $this->criticalSection->execute(__DIR__ . '/my/virtual/file1', $this->createCallbackThatMustBeInvoked());
        });
    }

    /**
     * Creates a closure that must be called, otherwise the test fails.
     *
     * @return \Closure
     */
    private function createCallbackThatMustBeInvoked()
    {
        $mock = $this->getMock(\stdClass::class, array('__invoke'));
        $mock->expects($this->once())
            ->method('__invoke');
        return function () use ($mock) {
            call_user_func($mock);
        };
    }
}