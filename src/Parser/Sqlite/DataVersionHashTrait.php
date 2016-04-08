<?php
namespace Crossjoin\Browscap\Parser\Sqlite;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

/**
 * Trait DataVersionHashTrait
 *
 * @package Crossjoin\Browscap\Parser\Sqlite
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
trait DataVersionHashTrait
{
    /**
     * @var string
     */
    protected $dataVersionHash = '';

    /**
     * @return string
     */
    protected function getDataVersionHash()
    {
        return $this->dataVersionHash;
    }

    /**
     * @param string $dataVersionHash
     *
     * @throws InvalidArgumentException
     */
    public function setDataVersionHash($dataVersionHash)
    {
        if (!is_string($dataVersionHash)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($dataVersionHash) . "' for argument 'dataVersionHash'."
            );
        }

        $this->dataVersionHash = $dataVersionHash;
    }
}
