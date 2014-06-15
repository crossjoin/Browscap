<?php
namespace Crossjoin\Browscap\Parser;

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
 * Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
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
 * @copyright Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 * @version 0.1
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
     *
     * @var string
     */
    protected $sourceType = 'PHP_BrowscapINI';

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
        if (self::$version === null) {
            $version = self::getCache()->get('browscap.version', false);
            if ($version !== null) {
                self::$version = (int)$version;
            }
        }
        return self::$version;
    }

    /**
     * Gets the browser data formatr for the given user agent
     * (or null if no data avaailble, no even the default browser)
     *
     * @param string $user_agent
     * @return \Crossjoin\Browscap\Formatter\AbstractFormatter|null
     */
    public function getBrowser($user_agent)
    {
        $formatter = null;

        foreach ($this->getPatterns($user_agent) as $patterns) {
            if (preg_match("/^(?:" . str_replace("\t", ")|(?:", $this->pregQuote($patterns)) . ")$/", $user_agent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");
                while ($pattern !== false) {
                    if (preg_match("/^" . $this->pregQuote($pattern) . "$/", $user_agent)) {
                        $formatter = \Crossjoin\Browscap\Browscap::getFormatter();
                        $formatter->setData($this->getSettings($pattern));
                        break 2;
                    }
                    $pattern = strtok("\t");
                }
            }
        }

        return $formatter;
    }

    /**
     * Sets a cache instance
     */
    public static function setCache(\Crossjoin\Browscap\Cache\AbstractCache $cache)
    {
        if (!($cache instanceof \Crossjoin\Browscap\Cache\File)) {
            throw new \InvalidArgumentException("This parser requires a cache instance of '\Crossjoin\Browscap\Cache\File'.");
        }
        self::$cache = $cache;
    }

    /**
     * Checks if the surce needs to be updated and processes the update
     */
    public function update($forceUpdate = false)
    {
        // get updater
        $updater = \Crossjoin\Browscap\Browscap::getUpdater();

        // check if an updater has been set - if not, nothing will be updated
        if ($updater !== null && ($updater instanceof \Crossjoin\Browscap\Updater\None) === false) {
            // do we have to check for a new update?
            $path     = self::getCache()->getFileName('browscap.ini', true);
            $readable = is_readable($path);
            if ($readable) {
                $localts = filemtime($path);
                $update  = $forceUpdate || ((time() - $localts) >= $updater->getInterval());
            } else {
                $localts = 0;
                $update  = true;
            }

            if ($update) {
                // check version/timestamp, to se if we need to do an update
                $do_update = false;
                if ($localts === 0) {
                    $do_update = true;
                } else {
                    $sourceversion = $updater->getBrowscapVersionNumber();
                    if ($sourceversion !== null && $sourceversion > $this->getVersion()) {
                        $do_update = true;
                    } else {
                        $sourcets = $updater->getBrowscapVersion();
                        if ($sourcets > $localts) {
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
                        $sourcecontent   = $updater->getBrowscapSource();
                        $sourceexception = null;
                    } catch (\Exception $e) {
                        $sourcecontent   = null;
                        $sourceexception = $e;
                    }
                    if (!empty($sourcecontent)) {
                        // update internal version cache first,
                        // to get the correct version for the next cache file
                        if (isset($sourceversion)) {
                            self::$version = (int)$sourceversion;
                        } else {
                            $key = $this->pregQuote(self::BROWSCAP_VERSION_KEY);
                            if (preg_match("/\.*[" . $key . "\][^[]*Version=(\d+)\D.*/", $sourcecontent, $matches)) {
                                if (isset($matches[1])) {
                                    self::$version = (int)$matches[1];
                                }
                            } else {
                                // ignore the error if...
                                // - we have old source data we can work with
                                // - and the data are loaded from a remote source
                                if ($readable && $updater instanceof \Crossjoin\Browscap\Updater\AbstractUpdaterRemote) {
                                    touch($path);
                                } else {
                                    throw new \RuntimeException("Problem parsing the INI file.");
                                }
                            }
                        }

                        // create cache file for the new version
                        self::getCache()->set('browscap.ini', $sourcecontent, true);
                        unset($sourcecontent);

                        // update cached version
                        self::getCache()->set('browscap.version', self::$version, false);

                        // reset cached ini data
                        $this->resetCachedData();
                    } else {
                        // ignore the error if...
                        // - we have old source data we can work with
                        // - and the data are loaded from a remote source
                        if ($readable && $updater instanceof \Crossjoin\Browscap\Updater\AbstractUpdaterRemote) {
                            touch($path);
                        } else {
                            throw new \RuntimeException("Error loading browscap source.", 0, $sourceexception);
                        }
                    }
                } else {
                    if ($readable) {
                        touch($path);
                    }
                }
            }
        }
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param string $user_agent
     */
    protected function getPatterns($user_agent)
    {
        $start  = $this->getPatternStart($user_agent);
        $length = strlen($user_agent);
        $subkey = $this->getPatternCacheSubkey($start);

        if (!self::getCache()->exists('browscap.patterns.' . $subkey)) {
            $this->createPatterns();
        }

        // get patterns, first for the given browser and if that is not found,
        // for the default browser (with a special key)
        $patternarr = array();
        foreach (array($start, str_repeat('z', 32)) as $tmp_start) {
            $tmp_subkey = $this->getPatternCacheSubkey($tmp_start);
            $file       = self::getCache()->getFileName('browscap.patterns.' . $tmp_subkey);
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
                                $patternarr[] = trim($patterns);
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
        return $patternarr;
    }

    /**
     * Creates new pattern cache files
     */
    protected function createPatterns()
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all('/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m', self::getContent(), $matches);
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
                $data[$tmp_start][$tmp_length][] = $match;
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

            // write optimized file (grouped by the first character of the has, generated from the pattern
            // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
            // array with pattern strings instead of an large array with single patterns) and also enables
            // us to search for multiple patterns in one preg_match call for a fast first search
            // (3-10 faster), followed by a detailed search for each single pattern.
            $contents = array();
            foreach ($data as $tmp_start => $tmp_entries) {
                foreach ($tmp_entries as $tmp_length => $tmp_patterns) {
                    for ($i = 0, $j = ceil(count($tmp_patterns)/$this->joinPatterns); $i < $j; $i++) {
                        $tmp_joinpatterns = implode("\t", array_slice($tmp_patterns, ($i * $this->joinPatterns), $this->joinPatterns));
                        $tmp_subkey       = $this->getPatternCacheSubkey($tmp_start);
                        if (!isset($contents[$tmp_subkey])) {
                            $contents[$tmp_subkey] = '';
                        }
                        $contents[$tmp_subkey] .= $tmp_start . " " . $tmp_length . " " . $tmp_joinpatterns . "\n";
                    }
                }
            }
            foreach ($contents as $subkey => $content) {
                self::getCache()->set('browscap.patterns.' . $subkey, $content, true);
            }
        }
    }

    /**
     * Gets the subkey for the pattern cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    protected function getPatternCacheSubkey($string) {
        return $string[0] . $string[1];
    }

    /**
     * Gets the content of the source file
     *
     * @return string
     */
    public static function getContent()
    {
        return (string)self::getCache()->get('browscap.ini', true);
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
        // set some additional data
        if (count($settings) === 0) {
            $settings['browser_name_regex']   = '/^' . $this->pregQuote($pattern) . '$/';
            $settings['browser_name_pattern'] = $pattern;
        }

        $add_settings = $this->getIniPart($pattern);

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
            return $this->getSettings($parent_pattern, $settings);
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
        $patternhash = md5($pattern);
        $subkey      = $this->getIniPartCacheSubkey($patternhash);

        if (!self::getCache()->exists('browscap.iniparts.' . $subkey)) {
            $this->createIniParts();
        }

        $return = array();
        $file   = self::getCache()->getFileName('browscap.iniparts.' . $subkey);
        $handle = fopen($file, "r");
        if ($handle) {
            while (($buffer = fgets($handle)) !== false) {
                if (substr($buffer, 0, 32) === $patternhash) {
                    $return = json_decode(substr($buffer, 32), true);
                    break;
                }
            }
            fclose($handle);
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
        // which part to use when the ini file is splitted into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $this->getContent(), $patternpositions);
        $patternpositions = $patternpositions[0];

        // split the ini file into sections and save the data in one line with a hash of the beloging
        // pattern (filtered in the previous step)
        $ini_parts = preg_split('/\[[^\r\n]+\]/', $this->getContent());
        $contents  = array();
        foreach ($patternpositions as $position => $pattern) {
            $patternhash = md5($pattern);
            $subkey      = $this->getIniPartCacheSubkey($patternhash);
            if (!isset($contents[$subkey])) {
                $contents[$subkey] = '';
            }

            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$subkey] .= $patternhash . json_encode(
                parse_ini_string($ini_parts[($position + 1)]),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) . "\n";
        }
        foreach ($contents as $chars => $content) {
            self::getCache()->set('browscap.iniparts.' . $chars, $content);
        }
    }

    /**
     * Gets the subkey for the ini parts cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    protected function getIniPartCacheSubkey($string) {
        return $string[0] . $string[1];
    }

    /**
     * Gets a hash from the first charcters of a pattern/user agent, that can be used for a fast comparison,
     * by comparing only the hashes, without having to match the complete pattern against the user agent.
     *
     * @param string $pattern
     * @return string
     */
    protected static function getPatternStart($pattern)
    {
        return md5(preg_replace('/^([^\*\?\s]*)[\*\?\s].*$/', '\\1', substr($pattern, 0, 32)));
    }

    /**
     * Gets the minimum length of the patern (used in the getPatterns() method to
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
}