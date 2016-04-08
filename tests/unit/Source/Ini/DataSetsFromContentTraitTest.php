<?php
namespace Crossjoin\Browscap\Tests\Unit\Source\Ini;

use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Source\DataSet;
use Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait;
use Crossjoin\Browscap\Tests\Mock\Source\Ini\DataSetsFromContent;

/**
 * Class DataSetsFromContentTraitTest
 *
 * @package Crossjoin\Browscap\Test\Source\Init
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait
 */
class DataSetsFromContentTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getContent
     */
    public function testContent()
    {
        /** @var DataSetsFromContentTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait');
        $content = '';
        foreach ($mock->getContent() as $data) {
            $content .= $data;
        }

        static::assertSame('', $content);
    }

    /**
     * @covers ::getDataSets
     *
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testValidDataSets()
    {
        $dataSetsMock = new DataSetsFromContent("[pattern*a]\nkey=value\n\n[pattern*b]\nkey=value\n\n");

        $i = 0;
        foreach ($dataSetsMock->getDataSets() as $dataSet) {
            $i++;
        }

        static::assertSame(2, $i);
    }

    /**
     * @covers ::getDataSetFromString
     */
    public function testValidData()
    {
        /** @var DataSetsFromContentTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait');

        $class = new \ReflectionClass($mock);
        $method = $class->getMethod('getDataSetFromString');
        $method->setAccessible(true);

        /** @var DataSet $result */
        $result = $method->invokeArgs($mock, ["[pattern]\nkey=\"value\""]);

        static::assertInstanceOf('\Crossjoin\Browscap\Source\DataSet', $result);
        static::assertSame('pattern', $result->getPattern());
        static::assertSame(['key' => 'value'], $result->getProperties());
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1459589758
     *
     * @covers ::getDataSetFromString
     */
    public function testEmptyData()
    {
        /** @var DataSetsFromContentTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait');

        $class = new \ReflectionClass($mock);
        $method = $class->getMethod('getDataSetFromString');
        $method->setAccessible(true);

        $method->invokeArgs($mock, ['']);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1459589759
     *
     * @covers ::getDataSetFromString
     */
    public function testInvalidData()
    {
        /** @var DataSetsFromContentTrait $mock */
        $mock = $this->getMockForTrait('\Crossjoin\Browscap\Source\Ini\DataSetsFromContentTrait');

        $class = new \ReflectionClass($mock);
        $method = $class->getMethod('getDataSetFromString');
        $method->setAccessible(true);

        $method->invokeArgs($mock, ["[pattern]\ninvalid = 'ini data"]);
    }
}
