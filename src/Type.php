<?php
namespace Crossjoin\Browscap;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    public static function isValid($type)
    {
        if (!is_int($type)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($type) . "' for argument 'type'."
            );
        }

        return in_array($type, [self::UNKNOWN, self::STANDARD, self::FULL, self::LITE], true);
    }

    /**
     * @param int $type
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getName($type)
    {
        if (!is_int($type)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($type) . "' for argument 'type'."
            );
        }

        $names = [self::UNKNOWN => 'unknown', self::STANDARD => 'standard', self::FULL => 'full', self::LITE => 'lite'];
        return array_key_exists($type, $names) ? $names[$type] : 'invalid';
    }
}
