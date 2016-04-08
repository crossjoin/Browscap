<?php
namespace Crossjoin\Browscap\Tests\Unit;

use Crossjoin\Browscap\Type;

/**
 * Class TypeTest
 *
 * @package Crossjoin\Browscap\Test
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Type
 */
class TypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataIsValid()
    {
        return [
            [Type::UNKNOWN, true],
            [Type::LITE, true],
            [Type::STANDARD, true],
            [Type::FULL, true],
            [-1, false],
            [1000, false],
        ];
    }

    /**
     * @return array
     */
    public function dataName()
    {
        return [
            [Type::UNKNOWN, 'unknown'],
            [Type::LITE, 'lite'],
            [Type::STANDARD, 'standard'],
            [Type::FULL, 'full'],
            [-1, 'invalid'],
            [1000, 'invalid'],
        ];
    }

    /**
     * @param $value
     * @param $expectedResult
     *
     * @dataProvider dataIsValid
     * @covers ::isValid
     */
    public function testIsValid($value, $expectedResult)
    {
        $result = Type::isValid($value);

        static::assertSame($expectedResult, $result);
    }

    /**
     * @param $type
     * @param $expectedResult
     *
     * @dataProvider dataName
     * @covers ::getName
     */
    public function testName($type, $expectedResult)
    {
        $result = Type::getName($type);

        static::assertSame($expectedResult, $result);
    }
}
