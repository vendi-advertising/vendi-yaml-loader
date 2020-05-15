<?php

declare(strict_types=1);

namespace Vendi\YamlLoader\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class YamlFileLoaderTestCase extends TestCase
{
    public const ENV_KEY = 'VENDI_YAML_FILE_FOR_TESTING_ONLY';

    // This is name of our FS root for testing
    private $_test_root_name = 'vendi-yaml-loader-test';

    // This is an instance of the Virtual File System
    /**
     * @var vfsStreamDirectory|null
     */
    private $_root;

    public function get_vfs_root(): vfsStreamDirectory
    {
        if (!$this->_root) {
            $this->_root = vfsStream::setup(
                $this->get_root_dir_name_no_trailing_slash(),
                null,
                []
            );
        }
        return $this->_root;
    }

    public function get_root_dir_name_no_trailing_slash(): string
    {
        return $this->_test_root_name;
    }

    public function setUp(): void
    {
        global $current_test_dir;
        $current_test_dir = null;
        $this->get_vfs_root();
        $this->reset_env();
    }

    public function tearDown(): void
    {
        global $current_test_dir;
        $current_test_dir = null;
        $this->reset_env();
    }

    private function reset_env()
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
        \putenv(self::ENV_KEY);
    }
}
