<?php
namespace Crossjoin\Browscap\Parser;

use Crossjoin\Browscap\Browscap;
use Crossjoin\Browscap\Cache;
use Crossjoin\Browscap\Formatter\FormatterInterface;
use Crossjoin\Browscap\Updater;

/**
 * Ini parser class (compatible with PHP 5.3+)
 *
 * This parser uses the standard PHP browscap.ini as its source. It requires
 * the file cache, because in most cases we work with files line by line
 * instead of using arrays, to keep the memory consumption as low as possible.
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
class IniLt55
extends AbstractParser
{
    /**
     * The key to search for in the INI file to find the browscap settings
     */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * The type to use when downloading the browscap source data
     * (default version: all browsers, default properties)
     *
     * @var string
     */
    protected $sourceType = 'PHP_BrowscapINI';

    /**
     * The type to use when downloading the browscap source data
     * (small version: popular browsers, default properties)
     *
     * @var string
     */
    protected $sourceTypeSmall = 'Lite_PHP_BrowscapINI';

    /**
     * The type to use when downloading the browscap source data
     * (large version: all browsers, extended properties)
     *
     * @var string
     */
    protected $sourceTypeLarge = 'Full_PHP_BrowscapINI';

    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    protected $joinPatterns = 100;

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion()
    {
        if (static::$version === null) {
            $prefix  = static::getCachePrefix();
            $version = static::getCache()->get("$prefix.version", false);
            if ($version !== null) {
                static::$version = (int)$version;
            }
        }
        return static::$version;
    }

    /**
     * Gets the browser data formatter for the given user agent
     * (or null if no data available, no even the default browser)
     *
     * @param string $user_agent
     * @return FormatterInterface|null
     */
    public function getBrowser($user_agent)
    {
        $formatter = null;

        foreach ($this->getPatterns($user_agent) as $patterns) {
            if (preg_match("/^(?:" . str_replace("\t", ")|(?:", $patterns) . ")$/i", $user_agent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");
                while ($pattern !== false) {
                    $pattern = str_replace('[\d]', '(\d)', $pattern);
                    if (preg_match('/^' . $pattern . '$/i', $user_agent, $matches)) {
                        // Insert the digits back into the pattern, so that we can search the settings for it
                        if (count($matches) > 1) {
                            array_shift($matches);
                            foreach ($matches as $one_match) {
                                $num_pos = strpos($pattern, '(\d)');
                                $pattern = substr_replace($pattern, $one_match, $num_pos, 4);
                            }
                        }

                        // Try to get settings - as digits have been replaced to speed up the pattern search (up to 90 faster),
                        // we won't always find the data in the first step - so check if settings have been found and if not,
                        // search for the next pattern.
                        $settings = $this->getSettings($pattern);
                        if (count($settings) > 0) {
                            $formatter = Browscap::getFormatter();
                            $formatter->setData($settings);
                            break 2;
                        }
                    }
                    $pattern = strtok("\t");
                }
            }
        }

        return $formatter;
    }

    /**
     * Sets a cache instance
     *
     * @param Cache\AbstractCache $cache
     */
    public static function setCache(Cache\AbstractCache $cache)
    {
        if (!($cache instanceof Cache\File)) {
            throw new \InvalidArgumentException(
                "This parser requires a cache instance of '\\Crossjoin\\Browscap\\Cache\\File'."
            );
        }
        static::$cache = $cache;
    }

    /**
     * Checks if the source needs to be updated and processes the update. This update includes the preparation
     * of the browscap data.
     *
     * The optional $forceUpdate argument always updates the data, no matter if required or not. This can produce
     * unnecessary load on the browscap servers and result in rate limit errors. It's not recommended to use this
     * option in production!
     *
     * @param boolean $forceUpdate
     * @throws \RuntimeException
     */
    public function update($forceUpdate = false)
    {
        // get updater
        $updater = Browscap::getUpdater();

        // check if an updater has been set - if not, nothing will be updated
        if ($updater !== null && ($updater instanceof Updater\None) === false) {
            // initialize variables
            $prefix   = static::getCachePrefix();
            /** @var Cache\File $cache */
            $cache    = static::getCache();
            $path     = $cache->getFileName("$prefix.ini", true);
            $readable = is_readable($path);
            $local_ts = 0;

            // do we have to check for a new update?
            if ($forceUpdate) {
                $update  = true;
            } else {
                if ($readable) {
                    $local_ts = filemtime($path);
                    $update  = ((time() - $local_ts) >= $updater->getInterval());
                } else {
                    $local_ts = 0;
                    $update  = true;
                }
            }

            if ($update) {
                // check version/timestamp, to se if we need to do an update
                $do_update = false;
                if ($local_ts === 0) {
                    $do_update = true;
                } else {
                    $source_version = $updater->getBrowscapVersionNumber();
                    if ($source_version !== null && $source_version > $this->getVersion()) {
                        $do_update = true;
                    } else {
                        $source_ts = $updater->getBrowscapVersion();
                        if ($source_ts > $local_ts) {
                            $do_update = true;
                        }
                    }
                }

                if ($do_update) {
                    // touch the file first so that the update is not triggered for some seconds,
                    // to avoid that the update is triggered by multiple users at the same time
                    if ($readable) {
                        $update_lock_time = 300;
                        touch($path, (time() - $updater->getInterval() + $update_lock_time));
                    }

                    // get content
                    try {
                        $source_content   = $updater->getBrowscapSource();
                        $source_exception = null;
                    } catch (\Exception $e) {
                        $source_content   = null;
                        $source_exception = $e;
                    }
                    if (!empty($source_content)) {
                        // update internal version cache first,
                        // to get the correct version for the next cache file
                        if (isset($source_version)) {
                            static::$version = (int)$source_version;
                        } else {
                            $key = $this->pregQuote(self::BROWSCAP_VERSION_KEY);
                            if (preg_match("/\\.*[" . $key . "\\][^[]*Version=(\\d+)\\D.*/", $source_content, $matches)) {
                                if (isset($matches[1])) {
                                    static::$version = (int)$matches[1];
                                }
                            } else {
                                // ignore the error if...
                                // - we have old source data we can work with
                                // - and the data are loaded from a remote source
                                if ($readable && $updater instanceof Updater\AbstractUpdaterRemote) {
                                    touch($path);
                                } else {
                                    throw new \RuntimeException("Problem parsing the INI file.");
                                }
                            }
                        }

                        // create cache file for the new version
                        static::getCache()->set("$prefix.ini", $source_content, true);
                        unset($source_content);

                        // Prepare the new data before the version gets updated. Otherwise request after the
                        // version update could also trigger the preparation (because of the new version, but no
                        // prepared data).
                        $this->createPatterns();
                        $this->createIniParts();

                        // update cached version
                        static::getCache()->set("$prefix.version", static::$version, false);

                        // reset cached ini data
                        $this->resetCachedData();
                    } else {
                        // ignore the error if...
                        // - we have old source data we can work with
                        // - and the data are loaded from a remote source
                        if ($readable && $updater instanceof Updater\AbstractUpdaterRemote) {
                            touch($path);
                        } else {
                            throw new \RuntimeException("Error loading browscap source.", 0, $source_exception);
                        }
                    }
                } else {
                    if ($readable) {
                        touch($path);
                    }
                }
            }
        } elseif ($forceUpdate === true) {
            throw new \RuntimeException("Required updater missing for forced update.");
        }
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param $user_agent
     * @return array
     */
    protected function getPatterns($user_agent)
    {
        $starts = $this->getPatternStart($user_agent, true);
        $length = strlen($user_agent);
        $prefix = static::getCachePrefix();

        // check if pattern files need to be created
        $pattern_file_missing = false;
        foreach ($starts as $start) {
            $sub_key = $this->getPatternCacheSubKey($start);
            if (!static::getCache()->exists("$prefix.patterns." . $sub_key)) {
                $pattern_file_missing = true;
                break;
            }
        }
        if ($pattern_file_missing === true) {
            $this->createPatterns();
        }

        // add special key to fall back to the default browser
        $starts[] = str_repeat('z', 32);

        // get patterns for the given start hashes
        $pattern_arr = array();
        foreach ($starts as $tmp_start) {
            $tmp_sub_key = $this->getPatternCacheSubKey($tmp_start);
            /** @var Cache\File $cache */
            $cache = static::getCache();
            $file  = $cache->getFileName("$prefix.patterns." . $tmp_sub_key);
            if (file_exists($file)) {
                $handle = fopen($file, "r");
                if ($handle) {
                    $found = false;
                    while (($buffer = fgets($handle)) !== false) {
                        $tmp_buffer = substr($buffer, 0, 32);
                        if ($tmp_buffer === $tmp_start) {
                            // get length of the pattern
                            $len = (int)strstr(substr($buffer, 33, 4), ' ', true);

                            // the user agent must be longer than the pattern without place holders
                            if ($len <= $length) {
                                list(,,$patterns) = explode(" ", $buffer, 3);
                                $pattern_arr[] = trim($patterns);
                            }
                            $found = true;
                        } elseif ($found === true) {
                            break;
                        }
                    }
                    fclose($handle);
                }
            }
        }
        return $pattern_arr;
    }

    /**
     * Creates new pattern cache files
     */
    protected function createPatterns()
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all('/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m', static::getContent(), $matches);
        $matches = $matches[0];

        if (count($matches)) {
            // build an array to structure the data. this requires some memory, but we need this step to be able to
            // sort the data in the way we need it (see below).
            $data = array();
            foreach ($matches as $match) {
                // get the first characters for a fast search
                $tmp_start  = $this->getPatternStart($match);
                $tmp_length = $this->getPatternLength($match);

                // special handling of default entry
                if ($tmp_length === 0) {
                    $tmp_start = str_repeat('z', 32);
                }

                if (!isset($data[$tmp_start])) {
                    $data[$tmp_start] = array();
                }
                if (!isset($data[$tmp_start][$tmp_length])) {
                    $data[$tmp_start][$tmp_length] = array();
                }

                $match = $this->pregQuote($match);

                // Check if the pattern contains digits - in this case we replace them with a digit regular expression,
                // so that very similar patterns (e.g. only with different browser version numbers) can be compressed.
                // This helps to speed up the first (and most expensive) part of the pattern search a lot.
                if (strpbrk($match, '0123456789') !== false) {
                    $compressedPattern = preg_replace('/\d/', '[\d]', $match);
                    if (!in_array($compressedPattern, $data[$tmp_start][$tmp_length])) {
                        $data[$tmp_start][$tmp_length][] = $compressedPattern;
                    }
                } else {
                    $data[$tmp_start][$tmp_length][] = $match;
                }
            }

            // sorting of the data is important to check the patterns later in the correct order, because
            // we need to check the most specific (=longest) patterns first, and the least specific
            // (".*" for "Default Browser")  last.
            //
            // sort by pattern start to group them
            ksort($data);
            // and then by pattern length (longest first)
            foreach (array_keys($data) as $key) {
                krsort($data[$key]);
            }

            // write optimized file (grouped by the first character of the hash, generated from the pattern
            // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
            // array with pattern strings instead of an large array with single patterns) and also enables
            // us to search for multiple patterns in one preg_match call for a fast first search
            // (3-10 faster), followed by a detailed search for each single pattern.
            $contents = array();
            foreach ($data as $tmp_start => $tmp_entries) {
                foreach ($tmp_entries as $tmp_length => $tmp_patterns) {
                    for ($i = 0, $j = ceil(count($tmp_patterns)/$this->joinPatterns); $i < $j; $i++) {
                        $tmp_join_patterns = implode("\t", array_slice($tmp_patterns, ($i * $this->joinPatterns), $this->joinPatterns));
                        $tmp_sub_key       = $this->getPatternCacheSubKey($tmp_start);
                        if (!isset($contents[$tmp_sub_key])) {
                            $contents[$tmp_sub_key] = '';
                        }
                        $contents[$tmp_sub_key] .= $tmp_start . " " . $tmp_length . " " . $tmp_join_patterns . "\n";
                    }
                }
            }

            // write cache files. important: also write empty cache files for
            // unused patterns, so that the regeneration is not unnecessarily
            // triggered by the getPatterns() method.
            $prefix   = static::getCachePrefix();
            $sub_keys = array_flip($this->getAllPatternCacheSubKeys());
            foreach ($contents as $sub_key => $content) {
                $sub_key = (string)$sub_key;
                static::getCache()->set("$prefix.patterns." . $sub_key, $content, true);
                unset($sub_keys[$sub_key]);
            }
            foreach (array_keys($sub_keys) as $sub_key) {
                $sub_key = (string)$sub_key;
                static::getCache()->set("$prefix.patterns." . $sub_key, '', true);
            }
        }
    }

    /**
     * Gets the sub key for the pattern cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    protected function getPatternCacheSubKey($string)
    {
        return $string[0] . $string[1];
    }

    /**
     * Gets all sub keys for the pattern cache files
     *
     * @return array
     */
    protected function getAllPatternCacheSubKeys()
    {
        $sub_keys = array();
        $chars   = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');

        foreach ($chars as $char_one) {
            foreach ($chars as $char_two) {
                $sub_keys[] = $char_one . $char_two;
            }
        }

        return $sub_keys;
    }

    /**
     * Gets the content of the source file
     *
     * @return string
     */
    public static function getContent()
    {
        $prefix = static::getCachePrefix();
        return (string)static::getCache()->get("$prefix.ini", true);
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param string $pattern
     * @param array $settings
     * @return array
     */
    protected function getSettings($pattern, $settings = array())
    {
        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = $this->pregUnQuote($pattern);

        // Try to get settings for the pattern
        $add_settings = $this->getIniPart($unquotedPattern);

        // set some additional data
        if (count($settings) === 0) {
            // The optimization with replaced digits get can now result in setting searches, for which we
            // won't find a result - so only add the pattern information, is settings have been found.
            //
            // If not an empty array will be returned and the calling function can easily check if a pattern
            // has been found.
            if (count($add_settings) > 0) {
                $settings['browser_name_regex']   = '/^' . $pattern . '$/';
                $settings['browser_name_pattern'] = $unquotedPattern;
            }
        }

        // check if parent pattern set, only keep the first one
        $parent_pattern = null;
        if (isset($add_settings['Parent'])) {
            $parent_pattern = $add_settings['Parent'];
            if (isset($settings['Parent'])) {
                unset($add_settings['Parent']);
            }
        }

        // merge settings
        $settings += $add_settings;

        if ($parent_pattern !== null) {
            return $this->getSettings($this->pregQuote($parent_pattern), $settings);
        }

        return $settings;
    }

    /**
     * Gets the relevant part (array of settings) of the ini file for a given pattern.
     *
     * @param string $pattern
     * @return array
     */
    protected function getIniPart($pattern)
    {
        $pattern_hash = md5($pattern);
        $sub_key      = $this->getIniPartCacheSubKey($pattern_hash);
        $prefix       = static::getCachePrefix();

        if (!static::getCache()->exists("$prefix.iniparts." . $sub_key)) {
            $this->createIniParts();
        }

        $return = array();
        /** @var Cache\File $cache */
        $cache  = static::getCache();
        $file   = $cache->getFileName("$prefix.iniparts." . $sub_key);
        if (file_exists($file)) {
            $handle = fopen($file, "r");
            if ($handle) {
                while (($buffer = fgets($handle)) !== false) {
                    if (substr($buffer, 0, 32) === $pattern_hash) {
                        $return = json_decode(substr($buffer, 32), true);
                        break;
                    }
                }
                fclose($handle);
            }
        }
        return $return;
    }

    /**
     * Creates new ini part cache files
     */
    protected function createIniParts()
    {
        // get all patterns from the ini file in the correct order,
        // so that we can calculate with index number of the resulting array,
        // which part to use when the ini file is split into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $this->getContent(), $pattern_positions);
        $pattern_positions = $pattern_positions[0];

        // split the ini file into sections and save the data in one line with a hash of the belonging
        // pattern (filtered in the previous step)
        $prefix    = static::getCachePrefix();
        $ini_parts = preg_split('/\[[^\r\n]+\]/', $this->getContent());
        $contents  = array();
        foreach ($pattern_positions as $position => $pattern) {
            $pattern_hash = md5($pattern);
            $sub_key      = $this->getIniPartCacheSubKey($pattern_hash);
            if (!isset($contents[$sub_key])) {
                $contents[$sub_key] = '';
            }

            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$sub_key] .= $pattern_hash . json_encode(
                parse_ini_string($ini_parts[($position + 1)]),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) . "\n";
        }

        // write cache files. important: also write empty cache files for
        // unused iniparts, so that the regeneration is not unnecessarily
        // triggered by the getIniParts() method.
        $sub_keys = array_flip($this->getAllIniPartCacheSubKeys());
        foreach ($contents as $chars => $content) {
            $chars = (string)$chars;
            static::getCache()->set("$prefix.iniparts." . $chars, $content);
            unset($sub_keys[$chars]);
        }
        foreach (array_keys($sub_keys) as $sub_key) {
            $sub_key = (string)$sub_key;
            static::getCache()->set("$prefix.iniparts." . $sub_key, '');
        }
    }

    /**
     * Gets the sub key for the ini parts cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    protected function getIniPartCacheSubKey($string)
    {
        return $string[0] . $string[1] . $string[2];
    }

    /**
     * Gets all sub keys for the inipart cache files
     *
     * @return array
     */
    protected function getAllIniPartCacheSubKeys()
    {
        $sub_keys = array();
        $chars   = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');

        foreach ($chars as $char_one) {
            foreach ($chars as $char_two) {
                foreach ($chars as $char_three) {
                    $sub_keys[] = $char_one . $char_two . $char_three;
                }
            }
        }

        return $sub_keys;
    }

    /**
     * Gets a hash or an array of hashes from the first characters of a pattern/user agent, that can
     * be used for a fast comparison, by comparing only the hashes, without having to match the
     * complete pattern against the user agent.
     *
     * With the variants options, all variants from the maximum number of pattern characters to one
     * character will be returned. This is required in some cases, the a placeholder is used very
     * early in the pattern.
     *
     * Example:
     *
     * Pattern: "Mozilla/* (Nintendo 3DS; *) Version/*"
     * User agent: "Mozilla/5.0 (Nintendo 3DS; U; ; en) Version/1.7567.US"
     *
     * In this case the has for the pattern is created for "Mozilla/" while the pattern
     * for the hash for user agent is created for "Mozilla/5.0". The variants option
     * results in an array with hashes for "Mozilla/5.0", "Mozilla/5.", "Mozilla/5",
     * "Mozilla/" ... "M", so that the pattern hash is included.
     *
     * @param string $pattern
     * @param boolean $variants
     * @return string|array
     */
    protected static function getPatternStart($pattern, $variants = false)
    {
        $string = preg_replace('/^([^\*\?\s]*)[\*\?\s].*$/', '\\1', substr($pattern, 0, 32));

        // use lowercase string to make the match case insensitive
        $string = strtolower($string);

        if ($variants === true) {
            $pattern_starts = array();
            for ($i = strlen($string); $i >= 1; $i--) {
                $string = substr($string, 0, $i);
                $pattern_starts[] = md5($string);
            }

            // Add empty pattern start to include patterns that start with "*",
            // e.g. "*FAST Enterprise Crawler*"
            $pattern_starts[] = md5("");

            return $pattern_starts;
        } else {
            return md5($string);
        }
    }

    /**
     * Gets the minimum length of the pattern (used in the getPatterns() method to
     * check against the user agent length)
     *
     * @param string $pattern
     * @return int
     */
    protected static function getPatternLength($pattern)
    {
        return strlen(str_replace('*', '', $pattern));
    }

    /**
     * Quotes a pattern from the browscap.ini file, so that it can be used in regular expressions
     *
     * @param string $pattern
     * @return string
     */
    protected static function pregQuote($pattern)
    {
        $pattern = preg_quote($pattern, "/");

        // The \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        // @source https://github.com/browscap/browscap-php
        return str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern);
    }

    /**
     * Reverts the quoting of a pattern.
     *
     * @param string $pattern
     * @return string
     */
    protected static function pregUnQuote($pattern)
    {
        // Fast check, because most parent pattern like 'DefaultProperties' don't need a replacement
        if (preg_match('/[^a-z\s]/i', $pattern)) {
            // Undo the \\x replacement, that is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
            // @source https://github.com/browscap/browscap-php
            $pattern = preg_replace(
                ['/(?<!\\\\)\\.\\*/', '/(?<!\\\\)\\./', '/(?<!\\\\)\\\\x/'],
                ['\\*', '\\?', '\\x'],
                $pattern
            );

            // Undo preg_quote
            $pattern = str_replace(
                array(
                    "\\\\", "\\+", "\\*", "\\?", "\\[", "\\^", "\\]", "\\\$", "\\(", "\\)", "\\{", "\\}", "\\=",
                    "\\!", "\\<", "\\>", "\\|", "\\:", "\\-", "\\.", "\\/"
                ),
                array(
                    "\\", "+", "*", "?", "[", "^", "]", "\$", "(", ")", "{", "}", "=", "!", "<", ">", "|", ":",
                    "-", ".", "/"
                ),
                $pattern
            );
        }
        return $pattern;
    }
}
