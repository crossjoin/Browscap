<?php
namespace Crossjoin\Browscap\Formatter;

/**
 * Formatter interface
 *
 * The formatter is used to convert the basic browscap settings
 * array into the preferred format. It can also be used to unset unnecessary
 * data or extend the result with additional data from other sources.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
interface FormatterInterface
{
    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    public function setData(array $settings);

    /**
     * Gets the data (in the preferred format)
     *
     * @return mixed
     */
    public function getData();
}
