<?php
namespace Crossjoin\Browscap\Parser;

/**
 * Ini parser class (compatible with PHP 5.5+)
 *
 * This parser overwrites parts of the basic ini parser class to use special
 * features form PHP 5.5 (generators) to optimize memory usage and performance.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class Ini extends IniLt55
{
    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param string $userAgent
     * @return \Generator
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
        foreach ($starts as $tmpStart) {
            $tmpSubKey = $this->getPatternCacheSubKey($tmpStart);
            /** @var \Crossjoin\Browscap\Cache\File $cache */
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
                            yield trim($patterns);
                        }
                        $found = true;
                    } elseif ($found === true) {
                        break;
                    }
                }
                fclose($handle);
            }
        }
        yield false;
    }
}
