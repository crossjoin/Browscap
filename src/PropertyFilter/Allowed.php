<?php
namespace Crossjoin\Browscap\PropertyFilter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

/**
 * Class Allowed
 *
 * @package Crossjoin\Browscap\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Allowed extends PropertyAbstract
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

        return !in_array(strtolower($property), $this->getProperties(), true);
    }
}
