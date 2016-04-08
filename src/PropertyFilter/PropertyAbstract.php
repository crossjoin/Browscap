<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\PropertyFilter;

/**
 * Class PropertyAbstract
 *
 * @package Crossjoin\Browscap\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
abstract class PropertyAbstract implements PropertyFilterInterface
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Allowed constructor.
     *
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        $this->setProperties($properties);
    }

    /**
     * @return array
     */
    public function getProperties() : array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     *
     * @return PropertyAbstract
     */
    public function setProperties(array $properties) : self
    {
        $this->properties = [];

        foreach ($properties as $property) {
            $this->addProperty($property);
        }

        return $this;
    }

    /**
     * @param string $property
     *
     * @return PropertyAbstract
     */
    public function addProperty(string $property) : self
    {
        $property = strtolower($property);
        if (!in_array($property, $this->properties, true)) {
            $this->properties[] = $property;
        }

        return $this;
    }
}
