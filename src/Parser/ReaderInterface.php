<?php
namespace Crossjoin\Browscap\Parser;

/**
 * Interface ReaderInterface
 *
 * @package Crossjoin\Browscap\Parser
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface ReaderInterface
{
    /**
     * Checks if the reader requires an update of the data, e.g. if the data source has not been generated yet
     * or deleted in the meantime.
     *
     * @return bool
     */
    public function isUpdateRequired();

    /**
     * Gets the current Browscap release time stamp (or 0 if not available)
     *
     * @return int
     */
    public function getReleaseTime();

    /**
     * Gets the current Browscap version number (or 0 if not available)
     *
     * @return int
     */
    public function getVersion();

    /**
     * Get the current Browscap type
     *
     * Values:
     * - \Crossjoin\Browscap\Type::STANDARD
     * - \Crossjoin\Browscap\Type::FULL
     * - \Crossjoin\Browscap\Type::LITE
     *
     * @return int
     */
    public function getType();

    /**
     * @param string $userAgent
     *
     * @return array
     */
    public function getBrowser($userAgent);
}
