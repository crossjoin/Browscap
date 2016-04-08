<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Formatter;

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
     */
    public function __construct(bool $returnArray = false)
    {
        $options = self::KEY_LOWER | self::VALUE_BOOLEAN_TO_STRING  | self::VALUE_REG_EXP_LOWER;
        if ($returnArray) {
            $options |= self::RETURN_ARRAY;
        } else {
            $options |= self::RETURN_OBJECT;
        }
        parent::__construct($options);
    }
}
