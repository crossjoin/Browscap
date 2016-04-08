<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

/**
 * Interface PreparedStatementInterface
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adpater
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface PreparedStatementInterface
{
    /**
     * @param array $params
     *
     * @return array
     */
    public function execute(array $params = []);
}
