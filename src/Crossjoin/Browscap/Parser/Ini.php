<?php
namespace Crossjoin\Browscap\Parser;

/**
 * Ini parser class (compatible with PHP 5.5+)
 *
 * This parser overwrites parts of the basic ini parser class to use special
 * features form PHP 5.5 (generators) to optimize memory usage and performance.
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
class Ini
extends IniLt55
{
    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param string $user_agent
     * @return \Generator
     */
    protected function getPatterns($user_agent)
    {
        $starts = $this->getPatternStart($user_agent, true);
        $length = strlen($user_agent);
        $prefix = static::getCachePrefix();

        // check if pattern files need to be created
        $pattern_file_missing = false;
        foreach ($starts as $start) {
            $sub_key = $this->getPatternCacheSubkey($start);
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
        foreach ($starts as $tmp_start) {
            $tmp_sub_key = $this->getPatternCacheSubkey($tmp_start);
            /** @var \Crossjoin\Browscap\Cache\File $cache */
            $cache = static::getCache();
            $file  = $cache->getFileName("$prefix.patterns." . $tmp_sub_key);
            if (file_exists($file)) {
                $handle = fopen($file, "r");
                if ($handle) {
                    try {
                        $found = false;
                        while (($buffer = fgets($handle)) !== false) {
                            $tmp_buffer = substr($buffer, 0, 32);
                            if ($tmp_buffer === $tmp_start) {
                                // get length of the pattern
                                $len = (int)strstr(substr($buffer, 33, 4), ' ', true);

                                // the user agent must be longer than the pattern without place holders
                                if ($len <= $length) {
                                    list(,,$patterns) = explode(" ", $buffer, 3);
                                    yield trim($patterns);
                                }
                                $found = true;
                            } elseif ($found === true) {
                                break;
                            }
                        }
                    } finally {
                        // always close the opened file, also when the Generator is
                        // used in a loop that is ended with a break
                        fclose($handle);
                    }
                }
            }
        }
        yield false;
    }
}