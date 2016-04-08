<?php
namespace Crossjoin\Browscap\PropertyFilter;

/**
 * Interface PropertyFilterInterface
 *
 * @package Crossjoin\Browscap\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface PropertyFilterInterface
{
    /**
     * Checks if a property is filtered by the current filter or not.
     * The check is case-insensitive.
     *
     * @param string $property
     *
     * @return bool
     */
    public function isFiltered($property);

    /**
     * Returns an indexed array with all property names.
     *
     * @return array
     */
    public function getProperties();
}
