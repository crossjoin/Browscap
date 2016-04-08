<?php
namespace Crossjoin\Browscap\Tests\Unit\PropertyFilter;

use Crossjoin\Browscap\PropertyFilter\Disallowed;

/**
 * Class DisallowedTest
 *
 * @package Crossjoin\Browscap\Test\Unit\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\PropertyFilter\Disallowed
 */
class DisallowedTest extends \PHPUnit_Framework_TestCase
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
        $filter = new Disallowed(['A', 'B']);
        static::assertTrue($filter->isFiltered('A'));
        static::assertTrue($filter->isFiltered('B'));
        static::assertFalse($filter->isFiltered('C'));
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
        $filter = new Disallowed(['A', 'B']);
        $filter->setProperties(['B', 'C']);
        static::assertFalse($filter->isFiltered('A'));
        static::assertTrue($filter->isFiltered('B'));
        static::assertTrue($filter->isFiltered('C'));
    }
}
