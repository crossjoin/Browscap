<?php
namespace Crossjoin\Browscap\Source;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

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
     *
     * @throws InvalidArgumentException
     */
    public function __construct($pattern)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($pattern) . "' for argument 'pattern'."
            );
        }

        $this->setPattern($pattern);
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     *
     * @throws InvalidArgumentException
     */
    protected function setPattern($pattern)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($pattern) . "' for argument 'pattern'."
            );
        }

        $this->pattern = $pattern;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     *
     * @return DataSet
     * @throws InvalidArgumentException
     */
    public function setProperties(array $properties)
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
     * @throws InvalidArgumentException
     */
    public function addProperty($key, $value)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($key) . "' for argument 'key'."
            );
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($value) . "' for argument 'value'."
            );
        }

        $this->properties[$key] = $value;

        return $this;
    }
}
