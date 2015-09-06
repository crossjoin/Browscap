<?php
namespace Crossjoin\Browscap;

/**
 * Main Crossjoin\Browscap class
 *
 * Crossjoin\Browscap allows to check for browser settings, using the data
 * from the Browscap project (browscap.org). It's about 40x faster than the
 * get_browser() function in PHP, with a very small memory consumption.
 *
 * It includes automatic updates of the Browscap data and allows to extends
 * or replace nearly all components: the updater, the parser (including the
 * used source), and the formatter (for the result set).
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
class Browscap
{
    /**
     * Current version of the package.
     * Has to be updated to automatically renew cache data.
     */
    const VERSION = '1.0.4';

    /**
     * Data set types
     */
    const DATASET_TYPE_DEFAULT = 1;
    const DATASET_TYPE_SMALL   = 2;
    const DATASET_TYPE_LARGE   = 3;

    /**
     * Use automatic updates (if no explicit updater set)
     *
     * @var \Crossjoin\Browscap\Updater\AbstractUpdater
     */
    protected $autoUpdate;

    /**
     * Updater to use
     *
     * @var \Crossjoin\Browscap\Updater\AbstractUpdater
     */
    protected static $updater;

    /**
     * Parser to use
     *
     * @var \Crossjoin\Browscap\Parser\AbstractParser
     */
    protected static $parser;

    /**
     * Formatter to use
     *
     * @var \Crossjoin\Browscap\Formatter\AbstractFormatter
     */
    protected static $formatter;

    /**
     * The data set type to use (default, small or large,
     * see constants).
     */
    protected static $datasetType = self::DATASET_TYPE_DEFAULT;

    /**
     * Probability in percent that the update check is done
     *
     * @var float
     */
    protected $updateProbability = 1.0;

    /**
     * Constructor
     *
     * @param bool $autoUpdate
     */
    public function __construct($autoUpdate = true)
    {
        $this->autoUpdate = (bool)(int)$autoUpdate;
    }

    /**
     * Checks the given/detected user agent and returns a
     * formatter instance with the detected settings
     *
     * @param string $user_agent
     * @return \Crossjoin\Browscap\Formatter\AbstractFormatter
     */
    public function getBrowser($user_agent = null)
    {
        // automatically detect the user agent
        if ($user_agent === null) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                $user_agent = '';
            }
        }

        // check for update first
        if ($this->autoUpdate === true && mt_rand(1, floor((100 / $this->updateProbability))) === 1) {
            static::getParser()->update();
        }

        // try to get browser data
        $return = static::getParser()->getBrowser($user_agent);

        // if not found, there has to be a problem with the source data,
        // because normally default browser data are returned,
        // so set the probability to 100%, to force an update.
        if ($return === null && $this->updateProbability < 100) {
            $updateProbability = $this->updateProbability;
            $this->updateProbability = 100;
            $return = $this->getBrowser($user_agent);
            $this->updateProbability = $updateProbability;
        }

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($return === null) {
            $return = static::getFormatter();
        }

        return $return;
    }

    /**
     * Set the formatter instance to use for the getBrowser() result
     *
     * @param \Crossjoin\Browscap\Formatter\AbstractFormatter $formatter
     */
    public static function setFormatter(Formatter\AbstractFormatter $formatter)
    {
        static::$formatter = $formatter;
    }

    /**
     * @return Formatter\AbstractFormatter
     */
    public static function getFormatter()
    {
        if (static::$formatter === null) {
            static::setFormatter(new Formatter\PhpGetBrowser());
        }
        return static::$formatter;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \Crossjoin\Browscap\Parser\AbstractParser $parser
     */
    public static function setParser(Parser\AbstractParser $parser)
    {
        static::$parser = $parser;
    }

    /**
     * @return Parser\AbstractParser
     */
    public static function getParser()
    {
        if (static::$parser === null) {
            // generators are supported from PHP 5.5, so select the correct parser version to use
            // (the version without generators requires about 2-3x the memory and is a bit slower)
            if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
                static::setParser(new Parser\Ini());
            } else {
                static::setParser(new Parser\IniLt55());
            }
        }
        return static::$parser;
    }

    /**
     * Sets the updater instance to use
     *
     * @param \Crossjoin\Browscap\Updater\AbstractUpdater $updater
     */
    public static function setUpdater(Updater\AbstractUpdater $updater)
    {
        static::$updater = $updater;
    }

    /**
     * Gets the updater instance (and initializes the default one, if not set)
     *
     * @return \Crossjoin\Browscap\Updater\AbstractUpdater
     */
    public static function getUpdater()
    {
        if (static::$updater === null) {
            $updater = Updater\FactoryUpdater::getInstance();
            if ($updater !== null) {
                static::setUpdater($updater);
            }
        }
        return static::$updater;
    }

    /**
     * Sets the data set type to use for the source.
     *
     * @param integer $datasetType
     * @throws \InvalidArgumentException
     */
    public static function setDatasetType ($datasetType)
    {
        if (in_array($datasetType, array(self::DATASET_TYPE_DEFAULT, self::DATASET_TYPE_SMALL, self::DATASET_TYPE_LARGE), true)) {
            static::$datasetType = $datasetType;
        } else {
            throw new \InvalidArgumentException("Invalid value for argument 'datasetType'.");
        }
    }

    /**
     * Gets the data set type to use for the source.
     *
     * @return integer
     */
    public static function getDatasetType ()
    {
        return static::$datasetType;
    }

    /**
     * Triggers an update check (with the option to force an update).
     *
     * @param boolean $forceUpdate
     */
    public static function update($forceUpdate = false)
    {
        static::getParser()->update($forceUpdate);
    }
}