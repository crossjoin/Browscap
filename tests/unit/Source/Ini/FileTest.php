<?php
namespace Crossjoin\Browscap\Tests\Unit\Source\Ini;

use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Source\Ini\File;
use Crossjoin\Browscap\Type;


/**
 * Class FileTest
 *
 * @package Crossjoin\Browscap\Test\Source\Init
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\Ini\File
 */
class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var File
     */
    protected $source;

    public function setUp()
    {
        parent::setUp();

        $iniFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'source' . DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';

        $this->source = new File($iniFile);
    }

    /**
     * @covers ::__construct
     * @covers \Crossjoin\Browscap\Source\FileAbstract::__construct
     * @covers \Crossjoin\Browscap\Source\FileAbstract::setFilePath
     * @covers \Crossjoin\Browscap\Source\FileAbstract::isFileReadable
     * @covers \Crossjoin\Browscap\Source\FileAbstract::getFilePath
     *
     * @throws SourceUnavailableException
     */
    public function testFilePathValid()
    {
        $iniFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'source' . DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';
        $source = new File($iniFile);

        $class = new \ReflectionClass('\Crossjoin\Browscap\Source\Ini\File');
        $method = $class->getMethod('getFilePath');
        $method->setAccessible(true);

        static::assertSame($iniFile, $method->invoke($source));
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     *
     * @covers ::__construct
     * @covers \Crossjoin\Browscap\Source\FileAbstract::setFilePath
     *
     * @throws SourceUnavailableException
     */
    public function testIniPathInvalid()
    {
        new File('invalid/path/to/browscap.ini');
    }

    /**
     * @covers ::getReleaseTime
     * @covers ::extractHeaderData
     *
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testReleaseTime()
    {
        static::assertGreaterThan(0, $this->source->getReleaseTime());
    }

    /**
     * @covers ::getVersion
     * @covers ::extractHeaderData
     *
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testVersion()
    {
        static::assertGreaterThan(0, $this->source->getVersion());
    }

    /**
     * @covers ::getType
     * @covers ::extractHeaderData
     *
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testType()
    {
        static::assertNotEquals(Type::UNKNOWN, $this->source->getType());
    }

    /**
     * @covers ::getContent
     * @covers \Crossjoin\Browscap\Source\FileAbstract::getContent
     *
     * @throws SourceUnavailableException
     */
    public function testContent()
    {
        static::assertInstanceOf('\Generator', $this->source->getContent());

        $content = '';
        foreach ($this->source->getContent() as $data) {
            $content .= $data;
            if ($content !== '') {
                break;
            }
        }
        static::assertNotEmpty($content);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1458977224
     */
    public function testFileUnreadable()
    {
        $iniFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'source' . DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';

        $source = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\File')
            ->disableOriginalConstructor()
            ->setMethods(['isFileReadable'])
            ->setMockClassName('MockUnreadableSourceIniFile')
            ->getMock();
        $source->expects(static::any())->method('isFileReadable')->willReturn(false);

        /** @noinspection PhpUndefinedClassInspection */
        new \MockUnreadableSourceIniFile($iniFile);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1458977225
     *
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testDataSourceUnavailable()
    {
        $iniFile = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
        'source' . DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';

        $tmpFile = tempnam(sys_get_temp_dir(), 'cb-');
        file_put_contents($tmpFile, file_get_contents($iniFile));

        $source = new File($tmpFile);
        unlink($tmpFile);
        $content = '';
        foreach ($source->getDataSets() as $dataSet) {
            // do nothing, but this loop is required to really call getData()
            $content .= ' ';
        }
    }
}