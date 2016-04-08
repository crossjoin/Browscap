<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Formatter;

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
     */
    public function __construct(bool $returnArray = false)
    {
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
