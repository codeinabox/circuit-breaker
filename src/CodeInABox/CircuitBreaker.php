<?php

namespace CodeInABox;

use \Doctrine\Common\Cache\Cache;

class CircuitBreaker {

    const CACHE_PREFIX = 'CircuitBreaker_failures_';

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $_cache;

    /**
     * The cache key for this circuit
     *
     * @var string
     */
    protected $_key;

    /**
     * @var integer
     */
    protected $_failureThreshold;

    /**
     * @var integer
     */
    protected $_checkTimeout;

    /**
     * 
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param string $resource
     * @param int $failureThreshold
     * @param int $checkTimeout
     */
    public function __construct(Cache $cache, $resource, $failureThreshold = 5, $checkTimeout = 60) {
        $this->_cache = $cache;
        $this->_key = self::CACHE_PREFIX . $resource;
        $this->_failureThreshold = (int) $failureThreshold;
        $this->_checkTimeout = (int) $checkTimeout;
    }

    /**
     * Get the failure count for the resource
     *
     * @return int
     */
    public function getFailureCount() {
        return $this->_cache->contains($this->_key) ? (int) $this->_cache->fetch($this->_key) : 0;
    }

    /**
     *
     * @return boolean
     */
    public function isBroken() {
        return $this->getFailureCount() > $this->_failureThreshold;
    }

    protected function _saveFailureCount($count) {
        $this->_cache->save($this->_key, (int) $count, $this->_checkTimeout);
    }

    /**
     * Increment the failure count
     *
     * @return self
     */
    public function failure() {
        $this->_saveFailureCount($this->getFailureCount() + 1);
        return $this;
    }

    /**
     * Mark the resource in success state and resets the failure count for the resource
     *
     * @return self
     */
    public function success() {
        $this->_saveFailureCount(0);
        return $this;
    }

}