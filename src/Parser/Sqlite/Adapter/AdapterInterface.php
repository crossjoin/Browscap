<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

/**
 * Interface AdapterInterface
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface AdapterInterface
{
    /**
     * @return bool
     */
    public function beginTransaction() : bool;

    /**
     * @return bool
     */
    public function commitTransaction() : bool;

    /**
     * @param string $query
     *
     * @return array
     */
    public function query(string $query) : array;

    /**
     * @param string $query
     *
     * @return PreparedStatementInterface
     */
    public function prepare(string $query) : PreparedStatementInterface;

    /**
     * @param string $query
     *
     * @return bool
     */
    public function exec(string $query) : bool;
}
