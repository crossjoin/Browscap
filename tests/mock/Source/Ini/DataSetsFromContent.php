<?php
namespace Crossjoin\Browscap\Tests\Mock\Source\Ini;

use Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait;

/**
 * Class DataSetsFromContent
 *
 * @package Crossjoin\Browscap\Tests\Mock\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class DataSetsFromContent
{
    use DataSetsFromContentTrait;

    /**
     * @var string
     */
    protected $content = '';

    /**
     * DataSetsFromContent constructor.
     *
     * @param $content
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * @return \Generator
     */
    public function getContent()
    {
        yield $this->content;
    }
}
