<?php

namespace Vendi\YamlLoader;

abstract class YamlLoaderBaseWithObjectCache extends YamlLoaderBase
{
    /**
     * @return mixed
     */
    final public function load_from_cache()
    {
        return wp_cache_get($this->cacheKey);
    }

    final public function save_to_cache(array $config): bool
    {
        return wp_cache_set($this->cacheKey, $config);
    }

    final public function remove_from_cache(): bool
    {
        return wp_cache_delete($this->cacheKey);
    }
}
