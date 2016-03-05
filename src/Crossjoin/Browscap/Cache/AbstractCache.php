<?php
namespace Crossjoin\Browscap\Cache;

/**
 * Abstract cache class
 *
 * This cache class is very simple, because the cache we use never expires.
 * So all we have are four basic methods, all with an option to cache the
 * data in dependence of the current version.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @deprecated Implement CacheInterface instead.
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * @param string $key
     * @param boolean $with_version
     * @return string|null
     */
    abstract public function get($key, $with_version = true);

    /**
     * Set cached data for a given key
     *
     * @param string $key
     * @param string $content
     * @param boolean $with_version
     * @return int|false
     */
    abstract public function set($key, $content, $with_version = true);

    /**
     * Delete cached data by a given key
     *
     * @param string $key
     * @param boolean $with_version
     * @return boolean
     */
    abstract public function delete($key, $with_version = true);

    /**
     * Check if a key is already cached
     *
     * @param string $key
     * @param boolean $with_version
     * @return boolean
     */
    abstract public function exists($key, $with_version = true);
}
