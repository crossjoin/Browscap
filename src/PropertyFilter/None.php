<?php
namespace Crossjoin\Browscap\PropertyFilter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

/**
 * Class None
 *
 * @package Crossjoin\Browscap\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class None implements PropertyFilterInterface
{
    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     */
    public function isFiltered($property)
    {
        if (!is_string($property)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($property) . "' for argument 'property'."
            );
        }
        
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getProperties()
    {
        return [];
    }
}
