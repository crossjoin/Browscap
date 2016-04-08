<?php
namespace Crossjoin\Browscap\Formatter;

use Crossjoin\Browscap\PropertyFilter\PropertyFilterInterface;

/**
 * Interface FormatterInterface
 *
 * @package Crossjoin\Browscap\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface FormatterInterface
{
    /**
     * Returns the used property filter (\Crossjoin\Browscap\PropertyFilter\None if no
     * special filter set).
     *
     * @return PropertyFilterInterface
     */
    public function getPropertyFilter();

    /**
     * Filters the properties when applying the format, so filtered properties are
     * not returned. This filter only influences the current output, not the parser.
     * If you want to filter the parser data so that properties are not contained in
     * the data and never returned, set the property filter in the parser instead
     * (see \Crossjoin\Browscap\Parser\ParserInterface).
     *
     * The property names are handled case-insensitive.
     *
     * @param PropertyFilterInterface $filter
     *
     * @return $this
     */
    public function setPropertyFilter(PropertyFilterInterface $filter);

    /**
     * Returns a formatted version of the given Browscap data
     *
     * @param array $browscapData
     *
     * @return mixed
     */
    public function format(array $browscapData);
}
