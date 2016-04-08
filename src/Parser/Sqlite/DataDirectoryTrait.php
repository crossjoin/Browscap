<?php
namespace Crossjoin\Browscap\Parser\Sqlite;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

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
    protected function getDataDirectory()
    {
        return $this->dataDirectory;
    }

    /**
     * @param string $dataDirectory
     *
     * @throws InvalidArgumentException
     */
    protected function setDataDirectory($dataDirectory)
    {
        if (!is_string($dataDirectory)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($dataDirectory) . "' for argument 'dataDirectory'."
            );
        }

        $this->dataDirectory = $dataDirectory;
    }
}
