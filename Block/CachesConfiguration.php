<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block;

use Magento\Framework\Config\CacheInterface;

/**
 * Trait encapsulating the ability of a Block Adminhtml element to "cache" the values
 * that it displays on configured cache.
 */
trait CachesConfiguration
{
    /**
     * @var CacheInterface
     */
    protected $configCache;

    /**
     * Set cache instance
     *
     * @param CacheInterface $cache
     * @return $this
     */
    protected function onCache($cache): self
    {
        $this->configCache = $cache;

        return $this;
    }

    /**
     * Cache configuration value on cache instance
     *
     * @param mixed $data
     * @param string $identifier
     * @param array $tags
     * @param mixed $lifeTime
     * @return bool
     */
    protected function cacheValue($data, $identifier, array $tags = [], $lifeTime = null): bool
    {
        if ($this->configCache) {
            return $this->configCache->save($data, $identifier, $tags, $lifeTime);
        }

        return false;
    }
}
