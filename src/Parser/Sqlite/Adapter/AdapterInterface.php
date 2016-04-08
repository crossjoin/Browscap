<?php
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
    public function beginTransaction();

    /**
     * @return bool
     */
    public function commitTransaction();

    /**
     * @param string $query
     *
     * @return array
     */
    public function query($query);

    /**
     * @param string $query
     *
     * @return PreparedStatementInterface
     */
    public function prepare($query);

    /**
     * @param string $query
     *
     * @return bool
     */
    public function exec($query);
}
