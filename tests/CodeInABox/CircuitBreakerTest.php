<?php

namespace CodeInABox;

use Mockery;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    protected function createMockCache()
    {
        return Mockery::mock('\Doctrine\Common\Cache\Cache');
    }

    public function testShouldHaveNoFailuresInitially()
    {
        $cache = $this->createMockCache();
        $cache->shouldReceive('contains')->once()->andReturn(false);
        $circuit = new CircuitBreaker($cache, 'resource');
        $this->assertEquals(0, $circuit->getFailureCount());
    }

    public function testShouldNotBeBrokenInitially()
    {
        $cache = $this->createMockCache();
        $cache->shouldReceive('contains')->once()->andReturn(false);
        $circuit = new CircuitBreaker($cache, 'resource', 6);
        $this->assertFalse($circuit->isBroken());
    }

    public function testShouldHaveFetchFailuresFromCache()
    {
        $cache = $this->createMockCache();
        $cache->shouldReceive('contains')->once()->andReturn(true);
        $cache->shouldReceive('fetch')->once()->andReturn(10);
        $circuit = new CircuitBreaker($cache, 'resource');
        $this->assertEquals(10, $circuit->getFailureCount());
    }

    public function testShouldBeBrokenIfFailuresExceedThreshold()
    {
        $cache = $this->createMockCache();
        $cache->shouldReceive('contains')->once()->andReturn(true);
        $cache->shouldReceive('fetch')->once()->andReturn(10);
        $circuit = new CircuitBreaker($cache, 'resource', 6);
        $this->assertTrue($circuit->isBroken());
    }

    public function testShouldOnFirstFailureSetCountToOne()
    {
        $checkTimeout = 60;
        $cache = $this->createMockCache();
        $cache->shouldReceive('contains')->once()->andReturn(false);
        $cache->shouldReceive('save')->once()->with(Mockery::any(), 1, $checkTimeout);
        $circuit = new CircuitBreaker($cache, 'resource', 10, $checkTimeout);
        $circuit->failure();
    }

    public function testShouldIncrementCountOnFailure()
    {
        $checkTimeout = 60;
        $cache = $this->createMockCache();
        $cache->shouldReceive('contains')->once()->andReturn(true);
        $cache->shouldReceive('fetch')->once()->andReturn(3);
        $cache->shouldReceive('save')->once()->with(Mockery::any(), 4, $checkTimeout);
        $circuit = new CircuitBreaker($cache, 'resource', 10, $checkTimeout);
        $circuit->failure();
    }

    public function testShouldResetCountOnSuccess()
    {
        $checkTimeout = 60;
        $cache = $this->createMockCache();
        $cache->shouldReceive('save')->once()->with(Mockery::any(), 0, $checkTimeout);
        $circuit = new CircuitBreaker($cache, 'resource', 10, $checkTimeout);
        $circuit->success();
    }
}
