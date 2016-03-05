<?php
namespace Crossjoin\Browscap\Cache;

use Crossjoin\Browscap\Browscap;

/**
 * File cache class
 *
 * The file cache is the basic cache adapter that is used by default, because
 * it's always available.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class File implements CacheInterface
{
    /**
     * @var string
     */
    protected static $cache_dir;

    /**
     * Get cached data by a given key
     *
     * @param string $key
     * @param boolean $with_version
     * @return string|null
     */
    public function get($key, $with_version = true)
    {
        $file = $this->getFileName($key, $with_version, false);
        if (is_readable($file)) {
            return file_get_contents($file);
        }
        return null;
    }

    /**
     * Set cached data for a given key
     *
     * @param string $key
     * @param string $content
     * @param boolean $with_version
     * @return int|false
     */
    public function set($key, $content, $with_version = true)
    {
        $file = $this->getFileName($key, $with_version, true);
        return file_put_contents($file, $content);
    }

    /**
     * Delete cached data by a given key
     *
     * @param string $key
     * @param boolean $with_version
     * @return boolean
     */
    public function delete($key, $with_version = true)
    {
        $file = $this->getFileName($key, $with_version, false);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * Check if a key is already cached
     *
     * @param string $key
     * @param boolean $with_version
     * @return boolean
     */
    public function exists($key, $with_version = true)
    {
        return file_exists($this->getFileName($key, $with_version, false));
    }

    /**
     * Gets the cache file name for a given key
     *
     * @param string $key
     * @param boolean $with_version
     * @param bool $create_dir
     * @return string
     */
    public function getFileName($key, $with_version = true, $create_dir = false)
    {
        $file  = static::getCacheDirectory($with_version, $create_dir);
        $file .= DIRECTORY_SEPARATOR . $key;

        return $file;
    }

    /**
     * Sets the (main) cache directory
     *
     * @param string $cache_dir
     */
    public static function setCacheDirectory($cache_dir)
    {
        static::$cache_dir = rtrim($cache_dir, DIRECTORY_SEPARATOR);
    }

    /**
     * Gets the main/version cache directory
     *
     * @param boolean $with_version
     * @param bool $create_dir
     * @return string
     */
    public static function getCacheDirectory($with_version = false, $create_dir = false)
    {
        // get sub directory name, depending on the data set type
        // (one sub directory for each data set type and version)
        switch (Browscap::getDatasetType()) {
            case Browscap::DATASET_TYPE_SMALL:
                $subDirName = 'smallbrowscap';
                break;
            case Browscap::DATASET_TYPE_LARGE:
                $subDirName = 'largebrowscap';
                break;
            default:
                $subDirName = 'browscap';
        }

        if (static::$cache_dir === null) {
            static::setCacheDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'browscap');
        }
        $path = static::$cache_dir;

        if ($with_version === true) {
            $path .= DIRECTORY_SEPARATOR . $subDirName;
            $path .= '_v' . Browscap::getParser()->getVersion();
            $path .= '_' . Browscap::VERSION;
        }

        if ($create_dir === true && !file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}
