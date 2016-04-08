<?php
namespace Crossjoin\Browscap\Tests\Unit\Source;

use Crossjoin\Browscap\Source\DataSet;

/**
 * Class DataSetTest
 *
 * @package Crossjoin\Browscap\Test\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\DataSet
 */
class DataSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::setPattern
     * @covers ::getPattern
     */
    public function testPattern()
    {
        $dataSet = new DataSet('JUC (Linux; U; 2.3*) UCWEB8.4*');
        static::assertSame('JUC (Linux; U; 2.3*) UCWEB8.4*', $dataSet->getPattern());
    }

    /**
     * @covers ::setProperties
     * @covers ::getProperties
     * @covers ::addProperty
     */
    public function testProperties()
    {
        $dataSet = new DataSet('JUC (Linux; U; 2.3*) UCWEB8.4*');
        $dataSet->setProperties(['A' => 'x', 'B' => 'y']);
        static::assertSame(['A' => 'x', 'B' => 'y'], $dataSet->getProperties());

        $dataSet->addProperty('C', 'z');
        static::assertSame(['A' => 'x', 'B' => 'y', 'C' => 'z'], $dataSet->getProperties());

        $dataSet->setProperties(['A' => 'x', 'B' => 'y']);
        static::assertSame(['A' => 'x', 'B' => 'y'], $dataSet->getProperties());
    }
}
