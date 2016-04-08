<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source;

use Crossjoin\Browscap\Type;

/**
 * Class None
 *
 * @package Crossjoin\Browscap\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class None implements SourceInterface, SourceFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function getReleaseTime() : int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getVersion() : int
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getType() : int
    {
        return Type::UNKNOWN;
    }

    /**
     * @inheritdoc
     */
    public function getDataSets() : \Iterator
    {
        return new \EmptyIterator();
    }
}
