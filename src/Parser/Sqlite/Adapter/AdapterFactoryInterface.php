<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

/**
 * Interface AdapterFactoryInterface
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
interface AdapterFactoryInterface
{
    /**
     * AdapterFactoryInterface constructor.
     *
     * @param string $fileName
     */
    public function __construct($fileName);
}
