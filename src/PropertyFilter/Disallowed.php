<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\PropertyFilter;

/**
 * Class Disallowed
 *
 * @package Crossjoin\Browscap\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Disallowed implements PropertyFilterInterface
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Disallowed constructor.
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
     * @return Disallowed
     */
    public function setProperties(array $properties) : Disallowed
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
     * @return Disallowed
     */
    public function addProperty(string $property) : Disallowed
    {
        $property = strtolower($property);
        if (!in_array($property, $this->properties, true)) {
            $this->properties[] = $property;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isFiltered(string $property) : bool
    {
        return in_array(strtolower($property), $this->getProperties(), true);
    }
}
