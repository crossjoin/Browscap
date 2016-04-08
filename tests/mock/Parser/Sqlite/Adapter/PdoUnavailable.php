<?php
namespace Crossjoin\Browscap\Tests\Mock\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Parser\Sqlite\Adapter\Pdo;

/**
 * Class PdoUnavailable
 *
 * @package Crossjoin\Browscap\Tests\Mock\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class PdoUnavailable extends Pdo
{
    /**
     * @return bool
     */
    protected function checkConditions()
    {
        return false;
    }
}
