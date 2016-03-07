<?php

namespace spec\CodeInABox;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Doctrine\Common\Cache\Cache;

class CircuitBreakerSpec extends ObjectBehavior
{
    public function it_should_have_no_failures_initially(Cache $cache)
    {
        $cache->contains(Argument::type('string'))->willReturn(false);
        $this->beConstructedWith($cache, 'resource');
        $this->getFailureCount()->shouldBe(0);
    }

    public function it_should_not_be_broken_initially(Cache $cache)
    {
        $cache->contains(Argument::type('string'))->willReturn(false);
        $this->beConstructedWith($cache, 'resource');
        $this->shouldNotBeBroken();
    }

    public function it_should_fetch_failure_count_from_cache(Cache $cache)
    {
        $cache->contains(Argument::type('string'))->willReturn(true);
        $cache->fetch(Argument::type('string'))->willReturn(10);
        $this->beConstructedWith($cache, 'resource');
        $this->getFailureCount()->shouldBe(10);
    }

    public function it_should_be_broken_if_failures_exceed_threshold(Cache $cache)
    {
        $cache->contains(Argument::type('string'))->willReturn(true);
        $cache->fetch(Argument::type('string'))->willReturn(10);
        $this->beConstructedWith($cache, 'resource', 6);
        $this->shouldBeBroken();
    }

    public function it_should_on_first_failure_set_count_to_one(Cache $cache)
    {
        $checkTimeout = 60;
        $cache->contains(Argument::type('string'))->willReturn(false);
        $cache->save(Argument::type('string'), 1, $checkTimeout)->shouldBeCalled();
        $this->beConstructedWith($cache, 'resource', 10, $checkTimeout);
        $this->failure();
    }

    public function it_test_should_increment_count_on_failure(Cache $cache)
    {
        $checkTimeout = 60;
        $cache->contains(Argument::type('string'))->willReturn(true);
        $cache->fetch(Argument::type('string'))->willReturn(3);
        $cache->save(Argument::type('string'), 4, $checkTimeout)->shouldBeCalled();
        $this->beConstructedWith($cache, 'resource', 10, $checkTimeout);
        $this->failure();
    }

    public function it_test_should_reset_count_on_success(Cache $cache)
    {
        $checkTimeout = 60;
        $cache->save(Argument::type('string'), 0, $checkTimeout)->shouldBeCalled();
        $this->beConstructedWith($cache, 'resource', 10, $checkTimeout);
        $this->success();
    }
}
