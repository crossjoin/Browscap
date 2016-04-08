<?php
namespace Crossjoin\Browscap\Tests\Unit\Source\Ini;

use Crossjoin\Browscap\Source\DataSet;
use Crossjoin\Browscap\Source\Ini\ParseHeaderSectionTrait;
use Crossjoin\Browscap\Type;

/**
 * Class ParseHeaderSectionTraitTest
 *
 * @package Crossjoin\Browscap\Test\Source\Init
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\Ini\ParseHeaderSectionTrait
 */
class ParseHeaderSectionTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataParser()
    {
        return [
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Version' => '1',
                    'Released' => 'Thu, 04 Feb 2016 12:59:23 +0000',
                    'Format' => 'php',
                    'Type' => 'LITE'
                ]),
                ['version' => 1, 'released' => 1454590763, 'type' => Type::LITE]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Version' => '1',
                    'Released' => 'Thu, 04 Feb 2016 12:59:23 +0000',
                    'Type' => 'LITE'
                ]),
                ['version' => 1, 'released' => 1454590763, 'type' => Type::LITE]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Version' => '1',
                    'Released' => 'Thu, 04 Feb 2016 12:59:23 +0000',
                    'Format' => 'php',
                    'Type' => ''
                ]),
                ['version' => 1, 'released' => 1454590763, 'type' => Type::STANDARD]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Version' => '1',
                    'Released' => 'Thu, 04 Feb 2016 12:59:23 +0000',
                    'Type' => 'FULL'
                ]),
                ['version' => 1, 'released' => 1454590763, 'type' => Type::FULL]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Version' => '1',
                    'Type' => 'FULL'
                ]),
                ['version' => 1, 'released' => 0, 'type' => Type::FULL]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Released' => 'Thu, 04 Feb 2016 12:59:23 +0000',
                    'Type' => 'FULL'
                ]),
                ['version' => 0, 'released' => 1454590763, 'type' => Type::FULL]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([
                    'Version' => '1',
                    'Released' => 'Thu, 04 Feb 2016 12:59:23 +0000',
                ]),
                ['version' => 1, 'released' => 1454590763, 'type' => Type::UNKNOWN]
            ],
            [
                (new DataSet('GJK_Browscap_Version'))->setProperties([]),
                ['version' => 0, 'released' => 0, 'type' => Type::UNKNOWN]
            ],
        ];
    }

    /**
     * @param DataSet $input
     * @param $expectedResult
     * @dataProvider dataParser
     */
    public function testParser(DataSet $input, $expectedResult)
    {
        /** @var ParseHeaderSectionTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\Source\Ini\ParseHeaderSectionTrait');

        $class = new \ReflectionClass($mock);
        $method = $class->getMethod('parseHeaderDataSet');
        $method->setAccessible(true);

        static::assertSame($expectedResult, $method->invokeArgs($mock, [$input]));
    }
}
