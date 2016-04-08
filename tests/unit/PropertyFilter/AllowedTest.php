<?php
namespace Crossjoin\Browscap\Tests\Unit\PropertyFilter;

use Crossjoin\Browscap\PropertyFilter\Allowed;

/**
 * Class AllowedTest
 *
 * @package Crossjoin\Browscap\Test\Unit\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\PropertyFilter\Allowed
 */
class AllowedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::setProperties
     * @covers ::addProperty
     * @covers ::getProperties
     * @covers ::isFiltered
     */
    public function testConstructorProperties()
    {
        $filter = new Allowed(['A', 'B']);
        static::assertFalse($filter->isFiltered('A'));
        static::assertFalse($filter->isFiltered('B'));
        static::assertTrue($filter->isFiltered('C'));
    }

    /**
     * @covers ::__construct
     * @covers ::setProperties
     * @covers ::addProperty
     * @covers ::getProperties
     * @covers ::isFiltered
     */
    public function testSetProperties()
    {
        $filter = new Allowed(['A', 'B']);
        $filter->setProperties(['B', 'C']);
        static::assertTrue($filter->isFiltered('A'));
        static::assertFalse($filter->isFiltered('B'));
        static::assertFalse($filter->isFiltered('C'));
    }
}
