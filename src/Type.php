<?php
declare(strict_types=1);

namespace Crossjoin\Browscap;

/**
 * Class Type
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
final class Type
{
    const UNKNOWN = 0;
    const STANDARD = 1;
    const FULL = 2;
    const LITE = 3;

    /**
     * @param int $type
     *
     * @return bool
     */
    public static function isValid(int $type) : bool
    {
        return in_array($type, [self::UNKNOWN, self::STANDARD, self::FULL, self::LITE], true);
    }

    /**
     * @param int $type
     *
     * @return string
     */
    public static function getName(int $type) : string
    {
        $names = [self::UNKNOWN => 'unknown', self::STANDARD => 'standard', self::FULL => 'full', self::LITE => 'lite'];
        return $names[$type] ?? 'invalid';
    }
}
