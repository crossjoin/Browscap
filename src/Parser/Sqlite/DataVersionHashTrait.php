<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Parser\Sqlite;

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
    protected function getDataVersionHash() : string
    {
        return $this->dataVersionHash;
    }

    /**
     * @param string $dataVersionHash
     */
    public function setDataVersionHash(string $dataVersionHash)
    {
        $this->dataVersionHash = $dataVersionHash;
    }
}
