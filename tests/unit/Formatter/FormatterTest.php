<?php
namespace Crossjoin\Browscap\Tests\Unit\Formatter;

use Crossjoin\Browscap\Formatter\Formatter;
use Crossjoin\Browscap\PropertyFilter\Allowed;

/**
 * Class FormatterTest
 *
 * @package Crossjoin\Browscap\Tests\Unit\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Formatter\Formatter
 */
class FormatterTest extends \PHPUnit_Framework_TestCase
{
    public function dataFormats()
    {
        $properties = [
            'Alpha' => 'false',
            'Beta' => 'true',
            'CssVersion' => '3',
            'Platform_Version' => 'ME',
            'RenderingEngine_Version' => '1.9.2',
            'Version' => '3.0',
            'browser_name_regex' => '^Mozilla.*$',
            'Platform' => 'unknown',
        ];

        return [
            [Formatter::KEY_LOWER, $properties, 'object', 'beta', 'true'],
            [Formatter::KEY_UPPER, $properties, 'object', 'BETA', 'true'],
            [Formatter::RETURN_ARRAY, $properties, 'array', 'Version', '3.0'],
            [Formatter::RETURN_OBJECT, $properties, 'object', 'Version', '3.0'],
            [Formatter::VALUE_BOOLEAN_TO_STRING, $properties, 'object', 'Alpha', ''],
            [Formatter::VALUE_BOOLEAN_TO_STRING, $properties, 'object', 'Beta', '1'],
            [Formatter::VALUE_REG_EXP_LOWER, $properties, 'object', 'browser_name_regex', '^mozilla.*$'],
            [Formatter::VALUE_TYPED, $properties, 'object', 'Alpha', false],
            [Formatter::VALUE_TYPED, $properties, 'object', 'Beta', true],
            [Formatter::VALUE_TYPED, $properties, 'object', 'CssVersion', 3],
            [Formatter::VALUE_TYPED, $properties, 'object', 'Platform_Version', 'ME'],
            [Formatter::VALUE_TYPED, $properties, 'object', 'RenderingEngine_Version', '1.9.2'],
            [Formatter::VALUE_TYPED, $properties, 'object', 'Version', 3.0],
            [Formatter::VALUE_TYPED, $properties, 'object', 'browser_name_regex', '^Mozilla.*$'],
            [Formatter::VALUE_TYPED, $properties, 'object', 'Platform', 'unknown'],
            [Formatter::VALUE_UNKNOWN_TO_NULL, $properties, 'object', 'Platform', null],
        ];
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param $options
     * @param $properties
     * @param $type
     * @param $key
     * @param $value
     *
     * @dataProvider dataFormats
     *
     * @covers ::__construct
     * @covers ::getOptions
     * @covers ::setOptions
     * @covers ::format
     * @covers ::modifyKey
     * @covers ::modifyValue
     * @covers ::hasBooleanValue
     * @covers ::hasIntegerValue
     * @covers ::hasFloatValue
     */
    public function testFormats($options, $properties, $type, $key, $value)
    {
        $formatter = new Formatter($options);
        $result = $formatter->format($properties);

        static::assertInternalType($type, $result);
        if (gettype($result) === 'object') {
            static::assertObjectHasAttribute($key, $result);
            $currentValue = property_exists($result, $key) ? $result->{$key} : 'none';
            static::assertEquals($value, $currentValue);
        } else {
            static::assertArrayHasKey($key, $result);
            $currentValue = array_key_exists($key, $result) ? $result[$key] : 'none';
            static::assertEquals($value, $currentValue);
        }
    }

    /**
     * @covers ::format
     */
    public function testPropertyFilter()
    {
        $properties = [
            'Alpha' => 'false',
            'Beta' => 'true',
        ];

        $propertyFilter = new Allowed();
        $propertyFilter->addProperty('Alpha');

        $formatter = new Formatter();
        $formatter->setPropertyFilter($propertyFilter);
        $result = $formatter->format($properties);

        static::assertObjectNotHasAttribute('Beta', $result);
    }
}
