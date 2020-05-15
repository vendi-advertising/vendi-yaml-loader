<?php

declare(strict_types=1);

namespace Vendi\YamlLoader;

use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

abstract class YamlLoaderBase implements YamlLoaderInterface
{
    /**
     * @var string
     */
    protected $envVariableForFile;

    /**
     * @var string
     */
    protected $defaultFileName;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @param array $config
     *
     * @return bool
     */
    abstract public function is_config_valid(array $config): bool;

    /**
     * @return mixed
     */
    abstract public function load_from_cache();

    /**
     * @param array $config
     *
     * @return bool
     */
    abstract public function save_to_cache(array $config): bool;

    /**
     * @return bool
     */
    abstract public function remove_from_cache(): bool;

    public function __construct(string $envVariableForFile, string $defaultFileName, string $cacheKey)
    {
        $this->envVariableForFile = $envVariableForFile;
        $this->defaultFileName = $defaultFileName;
        $this->cacheKey = $cacheKey;
    }

    public function get_default_config(): array
    {
        return [];
    }

    final public function get_env(string $name): string
    {
        $ret = \getenv($name);
        if (false === $ret) {
            return '';
        }
        return $ret;
    }

    final public function get_media_dir(): string
    {
        return \get_template_directory();
    }

    final public function get_yaml_file(): string
    {
        $file = $this->get_env($this->envVariableForFile);

        if ($file) {
            //I don't like this but Path::isAbsolute doesn't support stream wrappers
            if (\is_file($file)) {
                return $file;
            }

            //makeAbsolute doesn't work against streams, apparently
            return Path::makeAbsolute($file, $this->get_media_dir());
        }

        //This is the default
        return Path::join($this->get_media_dir(), '.config', $this->defaultFileName);
    }

    final public function get_config(): array
    {
        // Load from cache, is possible
        $ret = $this->load_from_cache();

        // If there's an array in there, assume it is valid
        if (is_array($ret)) {
            return $ret;
        }

        try {
            $ret = Yaml::parseFile($this->get_yaml_file());
        } catch (\Exception $ex) {
            // On failure, purge the cache (just in case) and return the default
            $this->remove_from_cache();
            return $this->get_default_config();
        }

        // Make sure we have valid data
        if (!is_array($ret) || !$this->is_config_valid($ret)) {
            $ret = $this->get_default_config();
        }

        // Cache it
        $this->save_to_cache($ret);
        return $ret;

    }
}
