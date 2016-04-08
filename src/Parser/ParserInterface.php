<?php
namespace Crossjoin\Browscap\Parser;

use Crossjoin\Browscap\PropertyFilter\PropertyFilterInterface;
use Crossjoin\Browscap\Source\SourceInterface;

/**
 * Interface ParserInterface
 *
 * @package Crossjoin\Browscap\Parser
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface ParserInterface
{
    /**
     * @return SourceInterface
     */
    public function getSource();

    /**
     * @param SourceInterface $source
     */
    public function setSource(SourceInterface $source);

    /**
     * Returns the used property filter (\Crossjoin\Browscap\PropertyFilter\None if no
     * special filter set).
     *
     * @return PropertyFilterInterface
     */
    public function getPropertyFilter();

    /**
     * Filters the properties when writing/reading the parser data. The filtered properties
     * will not be contained in the parser data (except they are required internally and
     * filtered when reading them), which reduces the data size and can improve performance.
     * If you want to filter the output for special cases only, add the filter to the used
     * Formatter instead (see \Crossjoin\Browscap\Formatter\FormatterInterface).
     *
     * The property names are handled case-insensitive.
     *
     * @param PropertyFilterInterface $filter
     *
     * @return $this
     */
    public function setPropertyFilter(PropertyFilterInterface $filter);

    /**
     * Get a reader instance for the parser.
     *
     * The argument $reInitiate is use to renew the reader instance. This is done by the
     * Browscap::update() method after the generation of new parser data, to ensure that the
     * reader works with the new data (perhaps it needs to be re-initiated).
     *
     * @param bool $reInitiate
     *
     * @return ReaderInterface
     */
    public function getReader($reInitiate = false);

    /**
     * @return WriterInterface
     */
    public function getWriter();
}
