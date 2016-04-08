<?php
namespace Crossjoin\Browscap\Source\Ini;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    protected function getRegExpForPattern($pattern)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($pattern) . "' for argument 'pattern'."
            );
        }

        $patternReplaced = str_replace(['*', '?'], ["\nA\n", "\nQ\n"], $pattern);
        $patternReplaced = preg_quote($patternReplaced, '/');
        $patternReplaced = str_replace(["\nA\n", "\nQ\n"], ['.*', '.'], $patternReplaced);

        return '/^' . $patternReplaced . '$/';
    }
}
