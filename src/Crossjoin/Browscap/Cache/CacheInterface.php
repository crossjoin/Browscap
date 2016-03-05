<?php
namespace Crossjoin\Browscap\Cache;

/**
 * Cache interface
 *
 * This cache class is very simple, because the cache we use never expires.
 * So all we have are four basic methods, all with an option to cache the
 * data in dependence of the current version.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @param boolean $with_version
     * @return string|null
     */
    public function get ($key, $with_version = true);

    /**
     * Set cached data for a given key
     *
     * @param string $key
     * @param string $content
     * @param boolean $with_version
     * @return int|false
     */
    public function set ($key, $content, $with_version = true);

    /**
     * Delete cached data by a given key
     *
     * @param string $key
     * @param boolean $with_version
     * @return boolean
     */
    public function delete ($key, $with_version = true);

    /**
     * Check if a key is already cached
     *
     * @param string $key
     * @param boolean $with_version
     * @return boolean
     */
    public function exists ($key, $with_version = true);
}
