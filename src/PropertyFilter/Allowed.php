<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\PropertyFilter;

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
     */
    public function isFiltered(string $property) : bool
    {
        return !in_array(strtolower($property), $this->getProperties(), true);
    }
}
