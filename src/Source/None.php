<?php
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
    public function getReleaseTime()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return Type::UNKNOWN;
    }

    /**
     * @inheritdoc
     */
    public function getDataSets()
    {
        return new \EmptyIterator();
    }
}
