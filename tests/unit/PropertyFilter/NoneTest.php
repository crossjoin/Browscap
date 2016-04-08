<?php
namespace Crossjoin\Browscap\Tests\Unit\PropertyFilter;

use Crossjoin\Browscap\PropertyFilter\None;

/**
 * Class NoneTest
 *
 * @package Crossjoin\Browscap\Test\Unit\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\PropertyFilter\None
 */
class NoneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::isFiltered
     */
    public function testConstructorProperties()
    {
        $filter = new None();
        static::assertFalse($filter->isFiltered('A'));
    }

    /**
     * @covers ::getProperties
     */
    public function testProperties()
    {
        $filter = new None();
        static::assertSame([], $filter->getProperties());
    }
}
