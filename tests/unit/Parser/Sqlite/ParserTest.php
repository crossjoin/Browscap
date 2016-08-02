<?php
namespace Crossjoin\Browscap\Tests\Unit\Parser\Sqlite;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\Sqlite\Parser;
use Crossjoin\Browscap\PropertyFilter\Disallowed;
use Crossjoin\Browscap\Source\Ini\File;

/**
 * Class ParserTest
 *
 * @package Crossjoin\Browscap\Test\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Parser\Sqlite\Parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::setDataDirectory
     * @covers ::checkDirectory
     * @covers ::isDirectoryReadable
     * @covers ::isDirectoryWritable
     * @covers ::getReader
     * @covers ::getDataDirectory
     * @covers ::getWriter
     * @covers ::getSource
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws UnexpectedValueException
     */
    public function testReaderAndWriter()
    {
        $parser = new Parser();
        $reader = $parser->getReader();
        $writer = $parser->getWriter();

        static::assertInstanceOf('\Crossjoin\Browscap\Parser\ReaderInterface', $reader);
        static::assertInstanceOf('\Crossjoin\Browscap\Parser\WriterInterface', $writer);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConfigurationException
     * @expectedExceptionCode 1458974127
     *
     * @covers ::__construct
     * @covers ::checkDirectory
     *
     * @throws ParserConfigurationException
     */
    public function testDirectoryMissing()
    {
        new Parser('');
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConfigurationException
     * @expectedExceptionCode 1458974128
     *
     * @covers ::checkDirectory
     * @covers ::isDirectoryReadable
     */
    public function testDirectoryNotReadable()
    {
        $parser = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Parser')
            ->disableOriginalConstructor()
            ->setMethods(['isDirectoryReadable'])
            ->setMockClassName('MockParserNotReadable')
            ->getMock();
        $parser->expects(static::any())->method('isDirectoryReadable')->willReturn(false);

        $directory = sys_get_temp_dir();
        
        /** @noinspection PhpUndefinedClassInspection */
        /** @var Parser $parser */
        $parser = new \MockParserNotReadable($directory);
        $parser->getReader();
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConfigurationException
     * @expectedExceptionCode 1458974129
     *
     * @covers ::checkDirectory
     * @covers ::isDirectoryWritable
     */
    public function testDirectoryNotWritable()
    {
        $parser = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Parser')
            ->disableOriginalConstructor()
            ->setMethods(['isDirectoryWritable'])
            ->setMockClassName('MockParserNotWritable')
            ->getMock();
        $parser->expects(static::any())->method('isDirectoryWritable')->willReturn(false);

        $directory = sys_get_temp_dir();

        /** @noinspection PhpUndefinedClassInspection */
        /** @var Parser $parser */
        $parser = new \MockParserNotWritable($directory);
        $parser->getWriter();
    }

    /**
     * @covers ::setSource
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws SourceUnavailableException
     * @throws UnexpectedValueException
     */
    public function testValidSource()
    {
        $iniFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'source' . DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';
        $source = new File($iniFile);

        $parser = new Parser();
        $parser->setSource($source);

        static::assertSame($source, $parser->getSource());
    }

    /**
     * @covers ::setPropertyFilter
     * @covers ::getPropertyFilter
     * @covers \Crossjoin\Browscap\Parser\Sqlite\DataVersionHashTrait::setDataVersionHash
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     */
    public function testPropertyFilter()
    {
        $propertyFilter = new Disallowed(['AolVersion']);
        $parser = new Parser();
        $parser->setPropertyFilter($propertyFilter);

        static::assertSame($propertyFilter, $parser->getPropertyFilter());
        static::assertSame($propertyFilter, $parser->getReader()->getPropertyFilter());
        static::assertSame($propertyFilter, $parser->getWriter()->getPropertyFilter());
    }

    /**
     * @covers ::getDataVersionHash
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws UnexpectedValueException
     */
    public function testDataVersion()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Parser');
        $method = $class->getMethod('getDataVersionHash');
        $method->setAccessible(true);

        $parser = new Parser();

        $parser2 = new Parser();
        $parser2->setPropertyFilter(new Disallowed(['AolVersion']));

        $dataVersion = $method->invokeArgs($parser, []);
        $dataVersion2 = $method->invokeArgs($parser2, []);

        static::assertNotSame($dataVersion, $dataVersion2);
        static::assertSame(40, strlen($dataVersion));
        static::assertSame(40, strlen($dataVersion2));
    }
}
