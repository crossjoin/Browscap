<?php
namespace Crossjoin\Browscap\Tests\Mock\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Parser\Sqlite\Adapter\Sqlite3;

/**
 * Class Sqlite3Unavailable
 *
 * @package Crossjoin\Browscap\Tests\Mock\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Sqlite3Unavailable extends Sqlite3
{
    /**
     * @return bool
     */
    protected function checkConditions()
    {
        return false;
    }
}
