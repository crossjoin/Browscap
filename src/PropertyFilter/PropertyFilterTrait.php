<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\PropertyFilter;

/**
 * Trait PropertyFilterTrait
 *
 * @package Crossjoin\Browscap\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
trait PropertyFilterTrait
{
    /**
     * @var PropertyFilterInterface
     */
    protected $propertyFilter;

    /**
     * @inheritdoc
     */
    public function getPropertyFilter() : PropertyFilterInterface
    {
        if ($this->propertyFilter === null) {
            $this->propertyFilter = new None();
        }

        return $this->propertyFilter;
    }

    /**
     * @inheritdoc
     */
    public function setPropertyFilter(PropertyFilterInterface $filter)
    {
        $this->propertyFilter = $filter;
    }
}
