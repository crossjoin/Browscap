<?php
namespace Crossjoin\Browscap\Source;

/**
 * Interface SourceInterface
 *
 * @package Crossjoin\Browscap\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface SourceInterface
{
    /**
     * Gets the source Browscap release time stamp (or 0 if not available)
     *
     * @return int
     */
    public function getReleaseTime();

    /**
     * Gets the source Browscap version number (or 0 if not available)
     *
     * @return int
     */
    public function getVersion();

    /**
     * Gets the source Browscap type
     *
     * Values:
     * - \Crossjoin\Browscap\Type::UNKNOWN
     * - \Crossjoin\Browscap\Type::STANDARD
     * - \Crossjoin\Browscap\Type::FULL
     * - \Crossjoin\Browscap\Type::LITE
     *
     * @return int
     */
    public function getType();

    /**
     * Gets the browscap source DataSet instances as Generator
     *
     * @return \Iterator|\Generator|DataSet
     */
    public function getDataSets();
}
