<?php

namespace CodeInABox;

use Doctrine\Common\Cache\Cache;

class CircuitBreaker
{
    const CACHE_PREFIX = 'CircuitBreakerfailures_';

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    /**
     * The cache key for this circuit.
     *
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $failureThreshold;

    /**
     * @var int
     */
    protected $checkTimeout;

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param string                       $resource
     * @param int                          $failureThreshold
     * @param int                          $checkTimeout
     */
    public function __construct(Cache $cache, $resource, $failureThreshold = 5, $checkTimeout = 60)
    {
        $this->cache = $cache;
        $this->key = self::CACHE_PREFIX.$resource;
        $this->failureThreshold = (int) $failureThreshold;
        $this->checkTimeout = (int) $checkTimeout;
    }

    /**
     * Get the failure count for the resource.
     *
     * @return int
     */
    public function getFailureCount()
    {
        return $this->cache->contains($this->key) ? (int) $this->cache->fetch($this->key) : 0;
    }

    /**
     * @return bool
     */
    public function isBroken()
    {
        return $this->getFailureCount() > $this->failureThreshold;
    }

    protected function saveFailureCount($count)
    {
        $this->cache->save($this->key, (int) $count, $this->checkTimeout);
    }

    /**
     * Increment the failure count.
     *
     * @return self
     */
    public function failure()
    {
        $this->saveFailureCount($this->getFailureCount() + 1);

        return $this;
    }

    /**
     * Mark the resource in success state and resets the failure count for the resource.
     *
     * @return self
     */
    public function success()
    {
        $this->saveFailureCount(0);

        return $this;
    }
}
