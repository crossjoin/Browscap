<?php
namespace Crossjoin\Browscap\Parser;

use Crossjoin\Browscap\Browscap;
use Crossjoin\Browscap\Cache;

/**
 * Abstract parser class
 *
 * The parser is the component, that parses a specific type of browscap source
 * data for the browser settings of a given user agent.
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2015 Christoph Ziegenberg <christoph@ziegenberg.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @copyright Copyright (c) 2014-2015 Christoph Ziegenberg <christoph@ziegenberg.com>
 * @version 1.0.4
 * @license http://www.opensource.org/licenses/MIT MIT License
 * @link https://github.com/crossjoin/browscap
 */
abstract class AbstractParser
{
    /**
     * Detected browscap version (read from INI file)
     *
     * @var int
     */
    protected static $version;

    /**
     * The cache instance
     *
     * @var \Crossjoin\Browscap\Cache\AbstractCache
     */
    protected static $cache;

    /**
     * The type to use when downloading the browscap source data
     * (default version: all browsers, default properties),
     * has to be overwritten by the extending class,
     * e.g. 'PHP_BrowscapINI'.
     *
     * @see http://browscap.org/
     * @var string
     */
    protected $sourceType = '';

    /**
     * The type to use when downloading the browscap source data
     * (small version: popular browsers, default properties),
     * has to be overwritten by the extending class,
     * e.g. 'Lite_PHP_BrowscapINI'.
     *
     * @see http://browscap.org/
     * @var string
     */
    protected $sourceTypeSmall = '';

    /**
     * The type to use when downloading the browscap source data
     * (large version: all browsers, extended properties),
     * has to be overwritten by the extending class,
     * e.g. 'Full_PHP_BrowscapINI'.
     *
     * @see http://browscap.org/
     * @var string
     */
    protected $sourceTypeLarge = '';

    /**
     * Gets the type of source to use
     *
     * @return string
     */
    public function getSourceType()
    {
        switch (Browscap::getDatasetType()) {
            case Browscap::DATASET_TYPE_SMALL:
                return $this->sourceTypeSmall;
            case Browscap::DATASET_TYPE_LARGE:
                return $this->sourceTypeLarge;
            default:
                return $this->sourceType;
        }
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    abstract public function getVersion();

    /**
     * Gets the browser data formatter for the given user agent
     * (or null if no data available, no even the default browser)
     *
     * @param string $user_agent
     * @return \Crossjoin\Browscap\Formatter\AbstractFormatter|null
     */
    abstract public function getBrowser($user_agent);

    /**
     * Gets a cache instance
     *
     * @return Cache\AbstractCache
     */
    public static function getCache()
    {
        if (static::$cache === null) {
            static::$cache = new Cache\File();
        }
        return static::$cache;
    }

    /**
     * Sets a cache instance
     *
     * @param Cache\AbstractCache $cache
     */
    public static function setCache(Cache\AbstractCache $cache)
    {
        static::$cache = $cache;
    }

    /**
     * Checks if the source needs to be updated and processes the update
     *
     * @param boolean $forceUpdate
     */
    abstract public function update($forceUpdate = false);

    /**
     * Resets cached data (e.g. the version) after an update of the source
     */
    public static function resetCachedData()
    {
        static::$version = null;
    }

    /**
     * Gets the cache prefix, dependent of the used browscap dataset type.
     *
     * @return string
     */
    protected static function getCachePrefix()
    {
        switch (Browscap::getDatasetType()) {
            case Browscap::DATASET_TYPE_SMALL:
                return 'smallbrowscap';
            case Browscap::DATASET_TYPE_LARGE:
                return 'largebrowscap';
            default:
                return 'browscap';
        }
    }
}