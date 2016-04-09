<?php
namespace Crossjoin\Browscap\Formatter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

/**
 * Class PhpGetBrowser
 *
 * @package Crossjoin\Browscap\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class PhpGetBrowser extends Formatter
{
    /**
     * PhpGetBrowser constructor.
     *
     * @param bool $returnArray
     *
     * @throws InvalidArgumentException
     */
    public function __construct($returnArray = false)
    {
        if (!is_bool($returnArray)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($returnArray) . "' for argument 'returnArray'."
            );
        }

        $options = self::KEY_LOWER | self::VALUE_BOOLEAN_TO_STRING | self::VALUE_REG_EXP_LOWER;
        if ($returnArray) {
            $options |= self::RETURN_ARRAY;
        } else {
            $options |= self::RETURN_OBJECT;
        }
        parent::__construct($options);
    }
}
