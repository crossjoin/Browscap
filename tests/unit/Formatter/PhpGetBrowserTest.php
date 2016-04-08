<?php
namespace Crossjoin\Browscap\Tests\Unit\Formatter;

use Crossjoin\Browscap\Formatter\PhpGetBrowser;

/**
 * Class PhpGetBrowserTest
 *
 * @package Crossjoin\Browscap\Tests\Unit\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Formatter\PhpGetBrowser
 */
class PhpGetBrowserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataFormat()
    {
        $expectedArray = ['foo' => 'Bar'];

        $expectedObject = new \stdClass();
        $expectedObject->foo = 'Bar';

        return [
            [true, ['Foo' => 'Bar'], $expectedArray],
            [true, ['Foo' => 'Baz', 'foo' => 'Bar'], $expectedArray],
            [false, ['Foo' => 'Bar'], $expectedObject],
            [false, ['Foo' => 'Baz', 'foo' => 'Bar'], $expectedObject],
        ];
    }

    /**
     * @param $returnArray
     * @param $value
     * @param $expectedResult
     *
     * @dataProvider dataFormat
     * @covers ::__construct
     */
    public function testFormat($returnArray, $value, $expectedResult)
    {
        $formatter = new PhpGetBrowser($returnArray);
        $result = $formatter->format($value);

        static::assertEquals($expectedResult, $result);
    }
}
