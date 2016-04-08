<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source\Ini;

/**
 * Trait GetRegExpForPatternTrait
 *
 * @package Crossjoin\Browscap\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
trait GetRegExpForPatternTrait
{
    /**
     * @param string $pattern
     *
     * @return string
     */
    protected function getRegExpForPattern(string $pattern) : string
    {
        $patternReplaced = str_replace(['*', '?'], ["\nA\n", "\nQ\n"], $pattern);
        $patternReplaced = preg_quote($patternReplaced, '/');
        $patternReplaced = str_replace(["\nA\n", "\nQ\n"], ['.*', '.'], $patternReplaced);

        return '/^' . $patternReplaced . '$/';
    }
}
