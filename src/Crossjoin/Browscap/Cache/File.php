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
    protected static $cacheDir;

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
     * @param boolean $withVersion
     * @param bool $createDir
     * @return string
     */
    public function getFileName($key, $withVersion = true, $createDir = false)
    {
        $file  = static::getCacheDirectory($withVersion, $createDir);
        $file .= DIRECTORY_SEPARATOR . $key;

        return $file;
    }

    /**
     * Sets the (main) cache directory
     *
     * @param string $cacheDir
     */
    public static function setCacheDirectory($cacheDir)
    {
        static::$cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
    }

    /**
     * Gets the main/version cache directory
     *
     * @param boolean $withVersion
     * @param bool $createDir
     * @return string
     */
    public static function getCacheDirectory($withVersion = false, $createDir = false)
    {
        // get sub directory name, depending on the data set type
        // (one sub directory for each data set type and version)
        switch (Browscap::getDataSetType()) {
            case Browscap::DATASET_TYPE_SMALL:
                $subDirName = 'smallbrowscap';
                break;
            case Browscap::DATASET_TYPE_LARGE:
                $subDirName = 'largebrowscap';
                break;
            default:
                $subDirName = 'browscap';
        }

        if (static::$cacheDir === null) {
            static::setCacheDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'browscap');
        }
        $path = static::$cacheDir;

        if ($withVersion === true) {
            $path .= DIRECTORY_SEPARATOR . $subDirName;
            $path .= '_v' . Browscap::getParser()->getVersion();
            $path .= '_' . Browscap::VERSION;
        }

        if ($createDir === true && !file_exists($path)) {
            /** @noinspection MkdirRaceConditionInspection */
            mkdir($path, 0777, true);
        }

        return $path;
    }
}
