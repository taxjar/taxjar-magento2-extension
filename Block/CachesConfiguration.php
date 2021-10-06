<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block;

/**
 * Trait encapsulating the ability of a Block Adminhtml element to "cache" the values
 * that it displays on configured cache.
 */
trait CachesConfiguration
{
    /**
     * @var
     */
    protected $configCache;

    /**
     * @param $cache
     * @return $this
     */
    protected function onCache($cache): self
    {
        $this->configCache = $cache;

        return $this;
    }

    /**
     * @param $data
     * @param $identifier
     * @param array $tags
     * @param null $lifeTime
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
