<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\PropertyFilter;

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
     */
    public function isFiltered(string $property) : bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getProperties() : array
    {
        return [];
    }
}
