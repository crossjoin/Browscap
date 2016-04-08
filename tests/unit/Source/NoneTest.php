<?php
namespace Crossjoin\Browscap\Tests\Unit\Source;

use Crossjoin\Browscap\Source\None;
use Crossjoin\Browscap\Type;

/**
 * Class NoneTest
 *
 * @package Crossjoin\Browscap\Test\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\None
 */
class NoneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var None
     */
    protected $source;

    public function setUp()
    {
        parent::setUp();

        $this->source = new None();
    }

    /**
     * @covers ::getReleaseTime
     */
    public function testReleaseTime()
    {
        static::assertSame(0, $this->source->getReleaseTime());
    }

    /**
     * @covers ::getVersion
     */
    public function testVersion()
    {
        static::assertSame(0, $this->source->getVersion());
    }

    /**
     * @covers ::getType
     */
    public function testType()
    {
        static::assertSame(Type::UNKNOWN, $this->source->getType());
    }

    /**
     * @covers ::getDataSets
     */
    public function testDataSets()
    {
        static::assertInstanceOf('\Iterator', $this->source->getDataSets());

        $content = '';
        foreach ($this->source->getDataSets() as $dataSet) {
            $content .= ' ';
        }
        static::assertSame('', $content);
    }
}
