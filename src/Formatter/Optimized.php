<?php
namespace Crossjoin\Browscap\Formatter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\PropertyFilter\Disallowed;

/**
 * Class Optimized
 *
 * @package Crossjoin\Browscap\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Optimized extends Formatter
{
    /**
     * Optimized constructor.
     *
     * @param bool $returnArray
     * @throws InvalidArgumentException
     */
    public function __construct($returnArray = false)
    {
        if (!is_bool($returnArray)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($returnArray) . "' for argument 'returnArray'."
            );
        }
        
        $options = self::VALUE_TYPED | self::VALUE_UNKNOWN_TO_NULL;
        if ($returnArray) {
            $options |= self::RETURN_ARRAY;
        } else {
            $options |= self::RETURN_OBJECT;
        }
        parent::__construct($options);

        // Disallow useless properties
        $propertyFilter = new Disallowed();
        $propertyFilter->addProperty('AolVersion');
        $this->setPropertyFilter($propertyFilter);
    }
}
