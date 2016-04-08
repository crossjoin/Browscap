<?php
declare(strict_types=1);

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
    public function isUpdateRequired() : bool;

    /**
     * Gets the current Browscap release time stamp (or 0 if not available)
     *
     * @return int
     */
    public function getReleaseTime() : int;

    /**
     * Gets the current Browscap version number (or 0 if not available)
     *
     * @return int
     */
    public function getVersion() : int;

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
    public function getType() : int;

    /**
     * @param string $userAgent
     *
     * @return array
     */
    public function getBrowser(string $userAgent) : array;
}
