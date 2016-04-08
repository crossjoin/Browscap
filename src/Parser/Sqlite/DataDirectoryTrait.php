<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Parser\Sqlite;

/**
 * Trait DataDirectoryTrait
 *
 * @package Crossjoin\Browscap\Parser\Sqlite
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
trait DataDirectoryTrait
{
    /**
     * @var string
     */
    protected $dataDirectory;

    /**
     * @return string
     */
    protected function getDataDirectory() : string
    {
        return $this->dataDirectory;
    }

    /**
     * @param string $dataDirectory
     */
    protected function setDataDirectory(string $dataDirectory)
    {
        $this->dataDirectory = $dataDirectory;
    }
}
