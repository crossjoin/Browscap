<?php
namespace Crossjoin\Browscap\Tests\Unit\PropertyFilter;

use Crossjoin\Browscap\PropertyFilter\Allowed;
use Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait;

/**
 * Class PropertyFilterTraitTest
 *
 * @package Crossjoin\Browscap\Test\Unit\PropertyFilter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait
 */
class PropertyFilterTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getPropertyFilter
     */
    public function testDefaultFilter()
    {
        /** @var PropertyFilterTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait');

        static::assertInstanceOf('\Crossjoin\Browscap\PropertyFilter\None', $mock->getPropertyFilter());
    }

    /**
     * @covers ::setPropertyFilter
     * @covers ::getPropertyFilter
     */
    public function testCustomFilter()
    {
        /** @var PropertyFilterTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait');
        $mock->setPropertyFilter(new Allowed());

        static::assertInstanceOf('\Crossjoin\Browscap\PropertyFilter\Allowed', $mock->getPropertyFilter());
    }
}
