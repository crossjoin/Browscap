<?php
namespace Crossjoin\Browscap\Tests\Unit\Parser\Sqlite;

use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Parser\Sqlite\Parser;
use Crossjoin\Browscap\Parser\Sqlite\Writer;
use Crossjoin\Browscap\Source\Ini\File;
use Crossjoin\Browscap\Source\SourceInterface;

/**
 * Class WriterTest
 *
 * @package Crossjoin\Browscap\Test\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Parser\Sqlite\Writer
 */
class WriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceInterface
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
     * @covers ::__construct()
     * @covers ::getSource()
     * @covers ::setSource()
     * @covers ::getTemporaryFileName()
     * @covers ::getAdapter()
     * @covers ::setAdapter()
     * @covers ::generate()
     * @covers ::initializeDatabase()
     * @covers ::getNextId()
     * @covers ::processDataSet()
     * @covers ::processHeader()
     * @covers ::processBrowserData()
     * @covers ::addParentPattern()
     * @covers ::getParentPatternId()
     * @covers ::getIdsForProperties()
     * @covers ::extractKeywordsFromPattern()
     * @covers ::finalizeDatabase()
     * @covers ::createIndexes()
     * @covers ::generateKeywordSearchTables()
     * @covers ::optimizeTableForReading()
     * @covers ::saveLink()
     * @covers ::getLinkPath()
     *
     * @throws ParserRuntimeException
     */
    public function testGeneration()
    {
        $directory = sys_get_temp_dir();
        $writer = new Writer($directory, $this->source);
        $writer->generate();

        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Writer');
        $method = $class->getMethod('getTemporaryFileName');
        $method->setAccessible(true);

        // Cleanup
        $temporaryFile = $method->invokeArgs($writer, []);
        unset($writer);
        unlink($temporaryFile);
        unlink(dirname($temporaryFile) . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::getParentPatternId
     */
    public function testParentNotFound()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Writer');
        $method = $class->getMethod('getParentPatternId');
        $method->setAccessible(true);

        $directory = sys_get_temp_dir();
        $writer = new Writer($directory, $this->source);
        $properties = ['Parent' => 'foo'];
        $method->invokeArgs($writer, [&$properties]);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::saveLink
     */
    public function testInvalidLinkPath()
    {
        $directory = sys_get_temp_dir();
        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Writer')
            ->setMethods(['getLinkPath'])
            ->setConstructorArgs([$directory, $this->source])
            ->getMock();
        $mock->expects(static::once())->method('getLinkPath')->willReturn('');

        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Writer');
        $method = $class->getMethod('saveLink');
        $method->setAccessible(true);
        $method->invokeArgs($mock, []);
    }

    /**
     * @covers ::cleanUp
     */
    public function testCleanUp()
    {
        $directory = sys_get_temp_dir();
        $writer = new Writer($directory, $this->source);

        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Writer');
        $method = $class->getMethod('getDataDirectory');
        $method->setAccessible(true);
        $dataDirectory = $method->invokeArgs($writer, []);

        $testFile = $dataDirectory . DIRECTORY_SEPARATOR . 'browscap_0.sqlite';
        file_put_contents($testFile, '');

        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Writer');
        $method = $class->getMethod('cleanUp');
        $method->setAccessible(true);
        $method->invokeArgs($writer, []);

        $result = file_exists($testFile);
        
        static::assertFalse($result);
    }
}
