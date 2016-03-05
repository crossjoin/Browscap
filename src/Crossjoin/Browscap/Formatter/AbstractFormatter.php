<?php
namespace Crossjoin\Browscap\Formatter;

/**
 * Abstract formatter class
 *
 * The formatter is used to convert the basic browscap settings
 * array into the preferred format. It can also be used to unset unnecessary
 * data or extend the result with additional data from other sources.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @deprecated Implement FormatterInterface instead.
 */
abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var mixed
     */
    protected $settings;

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    abstract public function setData(array $settings);

    /**
     * Gets the data (in the preferred format)
     *
     * @return mixed
     */
    abstract public function getData();
}
