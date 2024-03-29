<?php

declare(strict_types=1);

if (!function_exists('get_template_directory')) {
    function get_template_directory()
    {
        global $current_test_dir;

        return $current_test_dir;
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null)
    {
        global $wp_object_cache;
        if (!is_object($wp_object_cache)) {
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            $wp_object_cache = create_object_cache();
        }

        return $wp_object_cache->get($key, $group, $force, $found);
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0)
    {
        global $wp_object_cache;
        if (!is_object($wp_object_cache)) {
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            $wp_object_cache = create_object_cache();
        }

        return $wp_object_cache->set($key, $data, $group, (int)$expire);
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '')
    {
        global $wp_object_cache;
        if (!is_object($wp_object_cache)) {
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            $wp_object_cache = create_object_cache();
        }

        return $wp_object_cache->delete($key, $group);
    }
}

function create_object_cache(): object
{
    return new class() {
        private $cache = [];
        public $cache_hits = 0;
        public $cache_misses = 0;

        public function get($key, $group = 'default', $force = false, &$found = null)
        {
            if (empty($group)) {
                $group = 'default';
            }

            if ($this->_exists($key, $group)) {
                $found = true;
                $this->cache_hits++;
                if (is_object($this->cache[$group][$key])) {
                    return clone $this->cache[$group][$key];
                }

                return $this->cache[$group][$key];
            }

            $found = false;
            $this->cache_misses++;

            return false;
        }

        public function set($key, $data, $group = 'default', $expire = 0): bool
        {
            if (empty($group)) {
                $group = 'default';
            }

            if (is_object($data)) {
                $data = clone $data;
            }

            $this->cache[$group][$key] = $data;

            return true;
        }

        public function delete($key, $group = 'default', $deprecated = false): bool
        {
            if (empty($group)) {
                $group = 'default';
            }

            if (!$this->_exists($key, $group)) {
                return false;
            }

            unset($this->cache[$group][$key]);

            return true;
        }

        protected function _exists($key, $group): bool
        {
            return isset($this->cache[$group]) && (isset($this->cache[$group][$key]) || array_key_exists($key, $this->cache[$group]));
        }
    };
}
