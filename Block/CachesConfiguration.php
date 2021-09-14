<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block;

trait CachesConfiguration
{
    protected $configCache;

    protected function onCache($cache): self
    {
        $this->configCache = $cache;

        return $this;
    }

    protected function cacheValue($data, $identifier, array $tags = [], $lifeTime = null): bool
    {
        if ($this->configCache) {
            return $this->configCache->save($data, $identifier, $tags, $lifeTime);
        }

        return false;
    }


}
