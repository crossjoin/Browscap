<?php
namespace Crossjoin\Browscap\Parser;

use Crossjoin\Browscap\Browscap;
use Crossjoin\Browscap\Cache\CacheInterface;
use Crossjoin\Browscap\Cache\File;
use Crossjoin\Browscap\Formatter\FormatterInterface;
use Crossjoin\Browscap\Updater;

/**
 * Ini parser class (compatible with PHP 5.3+)
 *
 * This parser uses the standard PHP browscap.ini as its source. It requires
 * the file cache, because in most cases we work with files line by line
 * instead of using arrays, to keep the memory consumption as low as possible.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class IniLt55 extends AbstractParser
{
    /**
     * The key to search for in the INI file to find the browscap settings
     */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    protected $joinPatterns = 100;

    /**
     * IniLt55 constructor.
     */
    public function __construct()
    {
        // Set source type values
        $this->sourceType = 'PHP_BrowscapINI';
        $this->sourceTypeSmall = 'Lite_PHP_BrowscapINI';
        $this->sourceTypeLarge = 'Full_PHP_BrowscapINI';
    }

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
     * @param string $userAgent
     * @return FormatterInterface|null
     */
    public function getBrowser($userAgent)
    {
        $formatter = null;

        foreach ($this->getPatterns($userAgent) as $patterns) {
            if (preg_match('/^(?:' . str_replace("\t", ')|(?:', $patterns) . ')$/i', $userAgent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");
                while ($pattern !== false) {
                    $pattern = str_replace('[\d]', '(\d)', $pattern);
                    if (preg_match('/^' . $pattern . '$/i', $userAgent, $matches)) {
                        // Insert the digits back into the pattern, so that we can search the settings for it
                        if (count($matches) > 1) {
                            array_shift($matches);
                            foreach ($matches as $oneMatch) {
                                $numPos= strpos($pattern, '(\d)');
                                $pattern = substr_replace($pattern, $oneMatch, $numPos, 4);
                            }
                        }

                        // Try to get settings - as digits have been replaced to speed up the pattern search
                        // (up to 90 faster), we won't always find the data in the first step - so check if settings
                        // have been found and if not, search for the next pattern.
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
     * @param CacheInterface $cache
     * @throws \InvalidArgumentException
     */
    public static function setCache(CacheInterface $cache)
    {
        if (!($cache instanceof File)) {
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
     * @throws \InvalidArgumentException
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
            /** @var File $cache */
            $cache    = static::getCache();
            $path     = $cache->getFileName("$prefix.ini", true);
            $readable = is_readable($path);
            $localTimeStamp = 0;

            // do we have to check for a new update?
            if ($forceUpdate) {
                $update  = true;
            } else {
                if ($readable) {
                    $localTimeStamp = filemtime($path);
                    $update  = ((time() - $localTimeStamp) >= $updater->getInterval());
                } else {
                    $localTimeStamp = 0;
                    $update  = true;
                }
            }

            if ($update) {
                // Disable memory limit for update
                ini_set('memory_limit', -1);

                // check version/timestamp, to se if we need to do an update
                $doUpdate = false;
                if ($localTimeStamp === 0) {
                    $doUpdate = true;
                } else {
                    $sourceVersion = $updater->getBrowscapVersionNumber();
                    if ($sourceVersion !== null && $sourceVersion > $this->getVersion()) {
                        $doUpdate = true;
                    } else {
                        $sourceTimeStamp = $updater->getBrowscapVersion();
                        if ($sourceTimeStamp > $localTimeStamp) {
                            $doUpdate = true;
                        }
                    }
                }

                if ($doUpdate) {
                    // touch the file first so that the update is not triggered for some seconds,
                    // to avoid that the update is triggered by multiple users at the same time
                    if ($readable) {
                        $updateLockTime = 300;
                        touch($path, time() - $updater->getInterval() + $updateLockTime);
                    }

                    // get content
                    try {
                        $sourceContent   = $updater->getBrowscapSource();
                        $sourceException = null;
                    } catch (\Exception $e) {
                        $sourceContent   = '';
                        $sourceException = $e;
                    }
                    if ($sourceContent !== '') {
                        // update internal version cache first,
                        // to get the correct version for the next cache file
                        /** @noinspection UnSafeIsSetOverArrayInspection */
                        if (isset($sourceVersion)) {
                            static::$version = (int)$sourceVersion;
                        } else {
                            $key = static::pregQuote(self::BROWSCAP_VERSION_KEY);
                            if (preg_match(
                                "/\\.*[" . $key . "\\][^[]*Version=(\\d+)\\D.*/",
                                $sourceContent,
                                $matches
                            )) {
                                if (array_key_exists(1, $matches)) {
                                    static::$version = (int)$matches[1];
                                }
                            } else {
                                // ignore the error if...
                                // - we have old source data we can work with
                                // - and the data are loaded from a remote source
                                if ($readable && $updater instanceof Updater\AbstractUpdaterRemote) {
                                    touch($path);
                                } else {
                                    throw new \RuntimeException('Problem parsing the INI file.');
                                }
                            }
                        }

                        // create cache file for the new version
                        static::getCache()->set("$prefix.ini", $sourceContent, true);
                        unset($sourceContent);

                        // Prepare the new data before the version gets updated. Otherwise request after the
                        // version update could also trigger the preparation (because of the new version, but no
                        // prepared data).
                        $this->createPatterns();
                        $this->createIniParts();

                        // update cached version
                        static::getCache()->set("$prefix.version", static::$version, false);

                        // reset cached ini data
                        static::resetCachedData();
                    } else {
                        // ignore the error if...
                        // - we have old source data we can work with
                        // - and the data are loaded from a remote source
                        if ($readable && $updater instanceof Updater\AbstractUpdaterRemote) {
                            touch($path);
                        } else {
                            throw new \RuntimeException('Error loading browscap source.', 0, $sourceException);
                        }
                    }
                } else {
                    if ($readable) {
                        touch($path);
                    }
                }
            }
        } elseif ($forceUpdate === true) {
            throw new \RuntimeException('Required updater missing for forced update.');
        }
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param $userAgent
     * @return array
     */
    protected function getPatterns($userAgent)
    {
        $starts = static::getPatternStart($userAgent, true);
        $length = strlen($userAgent);
        $prefix = static::getCachePrefix();

        // check if pattern files need to be created
        $this->checkPatternFiles($starts);

        // add special key to fall back to the default browser
        $starts[] = str_repeat('z', 32);

        // get patterns for the given start hashes
        $patternArr = array();
        foreach ($starts as $tmpStart) {
            $tmpSubKey = $this->getPatternCacheSubKey($tmpStart);
            /** @var File $cache */
            $cache = static::getCache();
            $file  = $cache->getFileName("$prefix.patterns." . $tmpSubKey);
            if (!is_readable($file)) {
                continue;
            }

            $handle = fopen($file, 'r');
            if ($handle) {
                $found = false;
                while (($buffer = fgets($handle)) !== false) {
                    if (strpos($buffer, $tmpStart) === 0) {
                        // get length of the pattern
                        $len = (int)strstr(substr($buffer, 33, 4), ' ', true);

                        // the user agent must be longer than the pattern without place holders
                        if ($len <= $length) {
                            list(,,$patterns) = explode(' ', $buffer, 3);
                            $patternArr[] = trim($patterns);
                        }
                        $found = true;
                    } elseif ($found === true) {
                        break;
                    }
                }
                fclose($handle);
            }
        }
        return $patternArr;
    }

    /**
     * Checks if pattern files need to be created.
     *
     * @param array $patternStarts
     */
    protected function checkPatternFiles(array $patternStarts)
    {
        $patternFileMissing = false;
        $prefix = static::getCachePrefix();

        foreach ($patternStarts as $patternStart) {
            $subKey = $this->getPatternCacheSubKey($patternStart);
            if (!static::getCache()->exists("$prefix.patterns." . $subKey)) {
                $patternFileMissing = true;
                break;
            }
        }

        if ($patternFileMissing === true) {
            $this->createPatterns();
        }
    }

    /**
     * Creates new pattern cache files
     */
    protected function createPatterns()
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all(
            '/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m',
            static::getContent(),
            $matches
        );
        $matches = $matches[0];

        if (count($matches)) {
            // build an array to structure the data. this requires some memory, but we need this step to be able to
            // sort the data in the way we need it (see below).
            $data = array();
            foreach ($matches as $match) {
                // get the first characters for a fast search
                /** @var string $tmpStart */
                $tmpStart  = static::getPatternStart($match);
                $tmpLength = static::getPatternLength($match);

                // special handling of default entry
                if ($tmpLength === 0) {
                    $tmpStart = str_repeat('z', 32);
                }

                if (!array_key_exists($tmpStart, $data)) {
                    $data[$tmpStart] = array();
                }
                if (!array_key_exists($tmpLength, $data[$tmpStart])) {
                    $data[$tmpStart][$tmpLength] = array();
                }

                $match = static::pregQuote($match);

                // Check if the pattern contains digits - in this case we replace them with a digit regular expression,
                // so that very similar patterns (e.g. only with different browser version numbers) can be compressed.
                // This helps to speed up the first (and most expensive) part of the pattern search a lot.
                if (strpbrk($match, '0123456789') !== false) {
                    $compressedPattern = preg_replace('/\d/', '[\d]', $match);
                    if (!in_array($compressedPattern, $data[$tmpStart][$tmpLength], true)) {
                        $data[$tmpStart][$tmpLength][] = $compressedPattern;
                    }
                } else {
                    $data[$tmpStart][$tmpLength][] = $match;
                }
            }

            // sorting of the data is important to check the patterns later in the correct order, because
            // we need to check the most specific (=longest) patterns first, and the least specific
            // (".*" for "Default Browser")  last.
            //
            // sort by pattern start to group them
            ksort($data);
            // and then by pattern length (longest first)
            $keys = array_keys($data);
            foreach ($keys as $key) {
                krsort($data[$key]);
            }

            // write optimized file (grouped by the first character of the hash, generated from the pattern
            // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
            // array with pattern strings instead of an large array with single patterns) and also enables
            // us to search for multiple patterns in one preg_match call for a fast first search
            // (3-10 faster), followed by a detailed search for each single pattern.
            $contents = array();
            foreach ($data as $tmpStart => $tmpEntries) {
                foreach ($tmpEntries as $tmpLength => $tmpPatterns) {
                    for ($i = 0, $j = ceil(count($tmpPatterns)/$this->joinPatterns); $i < $j; $i++) {
                        $tmpJoinPatterns = implode(
                            "\t",
                            array_slice($tmpPatterns, $i * $this->joinPatterns, $this->joinPatterns)
                        );
                        $tmpSubKey = $this->getPatternCacheSubKey($tmpStart);
                        if (!array_key_exists($tmpSubKey, $contents)) {
                            $contents[$tmpSubKey] = '';
                        }
                        $contents[$tmpSubKey] .= $tmpStart . ' ' . $tmpLength . ' ' . $tmpJoinPatterns . "\n";
                    }
                }
            }

            // write cache files. important: also write empty cache files for
            // unused patterns, so that the regeneration is not unnecessarily
            // triggered by the getPatterns() method.
            $prefix   = static::getCachePrefix();
            $subKeys = array_flip($this->getAllPatternCacheSubKeys());
            foreach ($contents as $subKey => $content) {
                $subKey = (string)$subKey;
                static::getCache()->set("$prefix.patterns." . $subKey, $content, true);
                unset($subKeys[$subKey]);
            }
            $subKeys = array_keys($subKeys);
            foreach ($subKeys as $subKey) {
                $subKey = (string)$subKey;
                static::getCache()->set("$prefix.patterns." . $subKey, '', true);
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
        $subKeys = array();
        $chars   = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');

        foreach ($chars as $charOne) {
            foreach ($chars as $charTwo) {
                $subKeys[] = $charOne . $charTwo;
            }
        }

        return $subKeys;
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
    protected function getSettings($pattern, array $settings = array())
    {
        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = static::pregUnQuote($pattern);

        // Try to get settings for the pattern
        $addSettings = $this->getIniPart($unquotedPattern);

        // The optimization with replaced digits get can now result in setting searches, for which we
        // won't find a result - so only add the pattern information, is settings have been found.
        //
        // If not an empty array will be returned and the calling function can easily check if a pattern
        // has been found.
        if (count($settings) === 0 && count($addSettings) > 0) {
            $settings['browser_name_regex']   = '/^' . $pattern . '$/';
            $settings['browser_name_pattern'] = $unquotedPattern;
        }

        // check if parent pattern set, only keep the first one
        $parentPattern = null;
        if (array_key_exists('Parent', $addSettings)) {
            $parentPattern = $addSettings['Parent'];
            if (array_key_exists('Parent', $settings)) {
                unset($addSettings['Parent']);
            }
        }

        // merge settings
        /** @noinspection AdditionOperationOnArraysInspection */
        $settings += $addSettings;

        if ($parentPattern !== null) {
            return $this->getSettings(static::pregQuote($parentPattern), $settings);
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
        $patternHash = md5($pattern);
        $subKey      = $this->getIniPartCacheSubKey($patternHash);
        $prefix      = static::getCachePrefix();

        if (!static::getCache()->exists("$prefix.iniparts." . $subKey)) {
            $this->createIniParts();
        }

        $return = array();
        /** @var File $cache */
        $cache  = static::getCache();
        $file   = $cache->getFileName("$prefix.iniparts." . $subKey);
        if (file_exists($file)) {
            $handle = fopen($file, 'r');
            if ($handle) {
                while (($buffer = fgets($handle)) !== false) {
                    if (strpos($buffer, $patternHash) === 0) {
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
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', static::getContent(), $patternPositions);
        $patternPositions = $patternPositions[0];

        // split the ini file into sections and save the data in one line with a hash of the belonging
        // pattern (filtered in the previous step)
        $prefix   = static::getCachePrefix();
        $iniParts = preg_split('/\[[^\r\n]+\]/', static::getContent());
        $contents = array();
        foreach ($patternPositions as $position => $pattern) {
            $patternHash = md5($pattern);
            $subKey      = $this->getIniPartCacheSubKey($patternHash);
            if (!array_key_exists($subKey, $contents)) {
                $contents[$subKey] = '';
            }

            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$subKey] .= $patternHash . json_encode(
                parse_ini_string($iniParts[$position + 1]),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) . "\n";
        }

        // write cache files. important: also write empty cache files for
        // unused iniparts, so that the regeneration is not unnecessarily
        // triggered by the getIniParts() method.
        $subKeys = array_flip($this->getAllIniPartCacheSubKeys());
        foreach ($contents as $chars => $content) {
            $chars = (string)$chars;
            static::getCache()->set("$prefix.iniparts." . $chars, $content);
            unset($subKeys[$chars]);
        }
        $subKeys = array_keys($subKeys);
        foreach ($subKeys as $subKey) {
            $subKey = (string)$subKey;
            static::getCache()->set("$prefix.iniparts." . $subKey, '');
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
        $subKeys = array();
        $chars   = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');

        foreach ($chars as $charOne) {
            foreach ($chars as $charTwo) {
                foreach ($chars as $charThree) {
                    $subKeys[] = $charOne . $charTwo . $charThree;
                }
            }
        }

        return $subKeys;
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
            $patternStarts = array();
            for ($i = strlen($string); $i >= 1; $i--) {
                $string = substr($string, 0, $i);
                $patternStarts[] = md5($string);
            }

            // Add empty pattern start to include patterns that start with "*",
            // e.g. "*FAST Enterprise Crawler*"
            $patternStarts[] = md5('');

            return $patternStarts;
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
        $pattern = preg_quote($pattern, '/');

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
                array('/(?<!\\\\)\\.\\*/', '/(?<!\\\\)\\./', '/(?<!\\\\)\\\\x/'),
                array('\\*', '\\?', '\\x'),
                $pattern
            );

            // Undo preg_quote
            $pattern = str_replace(
                array(
                    "\\\\", "\\+", "\\*", "\\?", "\\[", "\\^", "\\]", "\\\$", "\\(", "\\)", "\\{", "\\}", "\\=",
                    "\\!", "\\<", "\\>", "\\|", "\\:", "\\-", "\\.", "\\/"
                ),
                array(
                    "\\", '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':',
                    '-', '.', '/'
                ),
                $pattern
            );
        }
        return $pattern;
    }
}
