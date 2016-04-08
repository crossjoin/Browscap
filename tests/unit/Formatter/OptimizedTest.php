<?php
namespace Crossjoin\Browscap\Tests\Unit\Formatter;

use Crossjoin\Browscap\Formatter\Optimized;

/**
 * Class OptimizedTest
 *
 * @package Crossjoin\Browscap\Tests\Unit\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Formatter\Optimized
 */
class OptimizedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataFormat()
    {
        $propertyArray = [
            'Alpha' => 'false',
            'Beta' => 'true',
            'CssVersion' => '3',
            'Platform_Version' => 'ME',
            'RenderingEngine_Version' => '1.9.2',
            'Version' => '3.0',
            'browser_name_regex' => '^Mozilla.*$',
            'Platform' => 'unknown',
        ];
        $expectedArray = [
            'Alpha' => false,
            'Beta' => true,
            'CssVersion' => 3,
            'Platform_Version' => 'ME',
            'RenderingEngine_Version' => '1.9.2',
            'Version' => 3.0,
            'browser_name_regex' => '^Mozilla.*$',
            'Platform' => null,
        ];
        $expectedObject = (object)$expectedArray;

        return [
            [true, $propertyArray, $expectedArray],
            [false, $propertyArray, $expectedObject],
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
        $formatter = new Optimized($returnArray);
        $result = $formatter->format($value);

        static::assertEquals($expectedResult, $result);
    }
}
