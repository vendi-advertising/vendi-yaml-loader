<?php

//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_touch
//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
//phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

namespace Vendi\YamlLoader\Tests;

use org\bovigo\vfs\vfsStream;
use Vendi\YamlLoader\YamlLoaderBase;
use Vendi\YamlLoader\YamlLoaderBaseWithObjectCache;
use Webmozart\PathUtil\Path;

class Test_YamlFile_Loader extends YamlFileLoaderTestCase
{
    public function get_simple_mock(string $envVariableForFile = null, string $defaultFileName = null, string $cacheKey = null): YamlLoaderBase
    {
        if (!$envVariableForFile) {
            $envVariableForFile = self::ENV_KEY;
        }

        if (!$defaultFileName) {
            $defaultFileName = 'test-config.yaml';
        }

        if (!$cacheKey) {
            $cacheKey = 'test-cache-key';
        }

        return new class ($envVariableForFile, $defaultFileName, $cacheKey) extends YamlLoaderBaseWithObjectCache {

            public function is_config_valid(array $config): bool
            {
                return true;
            }

            public function get_env_key(): string
            {
                return $this->envVariableForFile;
            }

            public function get_protected_variable(string $var)
            {
                return $this->$var;
            }

        };
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_env
     */
    public function test__get_env(): void
    {
        $mock = $this->get_simple_mock();
        $key = $mock->get_env_key();
        $this->assertFalse(\getenv($key));
        $this->assertSame('', $mock->get_env($key));
        \putenv("${key}=cheese");
        $this->assertSame('cheese', $mock->get_env($key));
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_media_dir
     */
    public function test__get_media_dir(): void
    {
        global $current_test_dir;
        $current_test_dir = '/cheese/';

        $this->assertSame('/cheese/', $this->get_simple_mock()->get_media_dir());
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_yaml_file
     */
    public function test__get_yaml_file(): void
    {
        global $current_test_dir;
        $current_test_dir = '/cheese/';

        $this->assertSame('/cheese/.config/test-config.yaml', $this->get_simple_mock()->get_yaml_file());
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_yaml_file
     */
    public function test__get_yaml_file__from_env(): void
    {
        global $current_test_dir;
        $current_test_dir = '/cheese/';

        $key = $this->get_simple_mock()->get_env_key();

        //This is absolute and will ignore the media_dir
        \putenv("${key}=/tmp/test.yaml");
        $this->assertSame('/tmp/test.yaml', $this->get_simple_mock()->get_yaml_file());

        //This is relative and will use the media_dir
        \putenv("${key}=./tmp/test.yaml");
        $this->assertSame('/cheese/tmp/test.yaml', $this->get_simple_mock()->get_yaml_file());
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_yaml_file
     */
    public function test__get_yaml_file__exists(): void
    {
        $key = $this->get_simple_mock()->get_env_key();

        $file = vfsStream::url(Path::join($this->get_root_dir_name_no_trailing_slash(), 'entry.yaml'));
        \putenv("${key}=${file}");
        \touch($file);
        $this->assertSame($file, $this->get_simple_mock()->get_yaml_file());
        unlink($file);
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_config
     */
    public function test__get_config(): void
    {
        $key = $this->get_simple_mock()->get_env_key();

        $file = vfsStream::url(Path::join($this->get_root_dir_name_no_trailing_slash(), 'entry.yaml'));
        \putenv("${key}=${file}");
        \touch($file);
        file_put_contents(
            $file,
            <<<'TAG'
alert:
    singular: Alert
    plural: Alerts
TAG

        );

        global $wp_object_cache;
        $wp_object_cache = create_object_cache();

        $this->assertSame(0, $wp_object_cache->cache_hits);
        $this->assertSame(0, $wp_object_cache->cache_misses);

        $config = $this->get_simple_mock()->get_config();
        $this->assertSame(0, $wp_object_cache->cache_hits);
        $this->assertSame(1, $wp_object_cache->cache_misses);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('alert', $config);
        $this->assertIsArray($config['alert']);
        $this->assertArrayHasKey('singular', $config['alert']);
        $this->assertArrayHasKey('plural', $config['alert']);
        $this->assertSame('Alert', $config['alert']['singular']);
        $this->assertSame('Alerts', $config['alert']['plural']);

        // Second call should hit the cache
        $config = $this->get_simple_mock()->get_config();
        $this->assertSame(1, $wp_object_cache->cache_hits);
        $this->assertSame(1, $wp_object_cache->cache_misses);
        unset($GLOBALS['wp_object_cache']);
        \putenv($key);
        unlink($file);
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_config
     */
    public function test__get_config__empty(): void
    {
        $key = $this->get_simple_mock()->get_env_key();

        $file = vfsStream::url(Path::join($this->get_root_dir_name_no_trailing_slash(), 'entry.yaml'));
        \putenv("${key}=${file}");
        \touch($file);
        $config = $this->get_simple_mock()->get_config();
        $this->assertEmpty($config);
        \putenv($key);
        unlink($file);
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_config
     */
    public function test__get_config__invalid(): void
    {
        $key = $this->get_simple_mock()->get_env_key();

        global $wp_object_cache;
        $wp_object_cache = create_object_cache();

        $file = vfsStream::url(Path::join($this->get_root_dir_name_no_trailing_slash(), 'entry.yaml'));
        \putenv("${key}=${file}");
        \touch($file);
        file_put_contents($file, ' -:');
        $this->assertSame([], $this->get_simple_mock()->get_config());
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::get_default_config
     */
    public function test__get_default_config(): void
    {
        $this->assertSame([], $this->get_simple_mock()->get_default_config());
    }

    /**
     * @covers \Vendi\YamlLoader\YamlLoaderBase::__construct
     */
    public function test__construct(): void
    {
        $mock = $this->get_simple_mock('a', 'b', 'c');
        $this->assertSame('a', $mock->get_protected_variable('envVariableForFile'));
        $this->assertSame('b', $mock->get_protected_variable('defaultFileName'));
        $this->assertSame('c', $mock->get_protected_variable('cacheKey'));
    }
}
