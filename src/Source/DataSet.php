<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source;

/**
 * Class DataSet
 *
 * @package Crossjoin\Browscap\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class DataSet
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * DataSet constructor.
     *
     * @param string $pattern
     */
    public function __construct(string $pattern)
    {
        $this->setPattern($pattern);
    }

    /**
     * @return string
     */
    public function getPattern() : string
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     */
    protected function setPattern(string $pattern)
    {
        $this->pattern = $pattern;
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
     * @return DataSet
     */
    public function setProperties(array $properties) : DataSet
    {
        $this->properties = [];

        foreach ($properties as $key => $value) {
            $this->addProperty($key, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return DataSet
     */
    public function addProperty(string $key, string $value) : DataSet
    {
        $this->properties[$key] = $value;

        return $this;
    }
}
