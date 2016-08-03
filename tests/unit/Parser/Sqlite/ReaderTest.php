<?php
namespace Crossjoin\Browscap\Tests\Unit\Parser\Sqlite;

use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\Sqlite\Parser;
use Crossjoin\Browscap\Parser\Sqlite\Reader;
use Crossjoin\Browscap\PropertyFilter\Disallowed;
use Crossjoin\Browscap\Type;

/**
 * Class ReaderTest
 *
 * @package Crossjoin\Browscap\Test\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Parser\Sqlite\Reader
 */
class ReaderTest extends \PHPUnit_Framework_TestCase
{
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0';

    /**
     * @var Reader
     */
    protected $reader;

    public function setUp()
    {
        parent::setUp();

        $directory = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'parser' . DIRECTORY_SEPARATOR . Parser::SUB_DIRECTORY;
        $this->reader = new Reader($directory);
    }

    /**
     * @covers ::__construct
     * @covers ::setDataDirectory
     * @covers ::getDataDirectory
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function testDataDirectory()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
        $method = $class->getMethod('getDataDirectory');
        $method->setAccessible(true);

        $directory = sys_get_temp_dir();
        $reader = new Reader($directory);
        $result = $method->invokeArgs($reader, []);

        static::assertSame($directory, $result);
    }

    /**
     * @covers ::getAdapter
     * @covers ::setAdapter
     * @covers ::getDatabasePath
     * @covers ::getDatabaseFileName
     * @covers ::isFileReadable
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function testAdapter()
    {
        // Simulate successfully created database
        $directory = sys_get_temp_dir();
        $databaseFileName = 'dummy.sqlite';
        $databaseFile = $directory . DIRECTORY_SEPARATOR . $databaseFileName;
        $linkFile = $directory . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        file_put_contents($databaseFile, '');
        file_put_contents($linkFile, $databaseFileName);

        try {
            $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
            $method = $class->getMethod('getAdapter');
            $method->setAccessible(true);

            $reader = new Reader($directory);
            $result = $method->invokeArgs($reader, []);

            static::assertInstanceOf('\Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterInterface', $result);
        } finally {
            unlink($databaseFile);
            unlink($linkFile);
        }
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1458898368
     *
     * @covers ::getAdapter
     * @covers ::getDatabasePath
     * @covers ::getDatabaseFileName
     * @covers ::isFileReadable
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function testLinkFileMissing()
    {
        $directory = sys_get_temp_dir();
        $linkFile = $directory . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        if (file_exists($linkFile)) {
            unlink($linkFile);
        }

        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
        $method = $class->getMethod('getAdapter');
        $method->setAccessible(true);

        $reader = new Reader($directory);
        $method->invokeArgs($reader, []);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1458898367
     *
     * @covers ::getAdapter
     * @covers ::getDatabasePath
     * @covers ::getDatabaseFileName
     * @covers ::isFileReadable
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function testLinkFileEmpty()
    {
        $directory = sys_get_temp_dir();
        $linkFile = $directory . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        file_put_contents($linkFile, '');

        try {
            $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
            $method = $class->getMethod('getAdapter');
            $method->setAccessible(true);

            $reader = new Reader($directory);
            $method->invokeArgs($reader, []);
        } finally {
            unlink($linkFile);
        }
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1458898365
     *
     * @covers ::getAdapter
     * @covers ::getDatabasePath
     * @covers ::getDatabaseFileName
     * @covers ::isFileReadable
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function testDatabaseFileMissing()
    {
        $directory = sys_get_temp_dir();
        $linkFile = $directory . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        file_put_contents($linkFile, 'missing.sqlite');

        try {
            $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
            $method = $class->getMethod('getAdapter');
            $method->setAccessible(true);

            $reader = new Reader($directory);
            $method->invokeArgs($reader, []);
        } finally {
            unlink($linkFile);
        }
    }

    /**
     * @covers ::isUpdateRequired
     * @covers \Crossjoin\Browscap\Parser\Sqlite\DataVersionHashTrait::getDataVersionHash
     *
     * @throws UnexpectedValueException
     */
    public function testNoUpdateRequired()
    {
        $this->reader->setDataVersionHash('89f88574e39cee94e52c033f320f6cb14e1ba1a1');
        static::assertFalse($this->reader->isUpdateRequired());
    }

    /**
     * @covers ::isUpdateRequired
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testUpdateRequiredRuntimeException()
    {
        $reader = new Reader('');
        $result = $reader->isUpdateRequired();

        static::assertTrue($result);
    }

    /**
     * @covers ::isUpdateRequired
     *
     * @throws UnexpectedValueException
     */
    public function testUpdateRequiredConditionNotSatisfiedException()
    {
        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Reader')
            ->setMethods(['getAdapter'])
            ->setConstructorArgs([''])
            ->getMock();
        $mock->expects(static::once())->method('getAdapter')
            ->willThrowException(new ParserConditionNotSatisfiedException());

        /** @var Reader $mock */
        $result = $mock->isUpdateRequired();

        static::assertTrue($result);
    }

    /**
     * @covers ::getReleaseTime
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testReleaseTime()
    {
        static::assertGreaterThan(0, $this->reader->getReleaseTime());
    }

    /**
     * @covers ::getVersion
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testVersion()
    {
        static::assertGreaterThan(0, $this->reader->getVersion());
    }

    /**
     * @covers ::getType
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testType()
    {
        static::assertSame(Type::LITE, $this->reader->getType());
    }

    /**
     * @covers ::getBrowser
     * @covers ::getBrowserId
     * @covers ::getBrowserParentId
     * @covers ::findBrowser
     * @covers ::findBrowserInKeywordTables
     * @covers ::findBrowserInDefaultTable
     * @covers ::getPatternKeywords
     * @covers ::sortProperties
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testUnknownBrowser()
    {
        $userAgent = 'fooBarBaz';
        $result = $this->reader->getBrowser($userAgent);

        static::assertInternalType('array', $result);
    }

    /**
     * @covers ::getBrowser
     * @covers ::getBrowserId
     * @covers ::getBrowserParentId
     * @covers ::findBrowser
     * @covers ::findBrowserInKeywordTables
     * @covers ::findBrowserInDefaultTable
     * @covers ::getPatternKeywords
     * @covers ::sortProperties
     * @covers ::getSqliteVersion
     * @covers \Crossjoin\Browscap\Source\Ini\GetRegExpForPatternTrait::getRegExpForPattern
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testKnownBrowser()
    {
        $result = $this->reader->getBrowser(self::USER_AGENT);

        static::assertInternalType('array', $result);
        static::assertEquals(
            'Mozilla/5.0 (*Windows NT 10.0*rv:45.0*) Gecko* Firefox*',
            $result['browser_name_pattern']
        );
    }

    /**
     * @covers ::getBrowser
     * @covers ::getBrowserId
     * @covers ::getBrowserParentId
     * @covers ::findBrowser
     * @covers ::findBrowserInKeywordTables
     * @covers ::findBrowserInDefaultTable
     * @covers ::getPatternKeywords
     * @covers ::sortProperties
     * @covers ::getSqliteVersion
     * @covers \Crossjoin\Browscap\Source\Ini\GetRegExpForPatternTrait::getRegExpForPattern
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testKnownBrowserWithOldSqliteVersion()
    {
        $directory = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'parser' . DIRECTORY_SEPARATOR . Parser::SUB_DIRECTORY;

        $reader = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Reader')
            ->setConstructorArgs([$directory])
            ->setMethods(['getSqliteVersion'])
            ->getMock();

        $reader->expects(static::any())->method('getSqliteVersion')->willReturn('3.6.20');

        /** @var Reader $reader */
        $result = $reader->getBrowser(self::USER_AGENT);

        static::assertInternalType('array', $result);
        static::assertEquals(
            'Mozilla/5.0 (*Windows NT 10.0*rv:45.0*) Gecko* Firefox*',
            $result['browser_name_pattern']
        );
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1458898369
     *
     * @covers ::getDatabaseFileName
     */
    public function testUnreadableLinkFile()
    {
        $directory = sys_get_temp_dir();
        $linkFile = $directory . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        file_put_contents($linkFile, 'dummy.sqlite');

        try {
            $reader = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Reader')
                ->setConstructorArgs([$directory])
                ->setMethods(['isFileReadable'])
                ->getMock();
            $reader->expects(static::any())->method('isFileReadable')->willReturnCallback(function($file) {
                return (strpos($file, '.sqlite') !== false);
            });

            $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
            $method = $class->getMethod('getAdapter');
            $method->setAccessible(true);

            $method->invokeArgs($reader, []);
        } finally {
            unlink($linkFile);
        }
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     * @expectedExceptionCode 1458898366
     *
     * @covers ::getDatabasePath
     */
    public function testUnreadableDatabaseFile()
    {
        $directory = sys_get_temp_dir();
        $linkFile = $directory . DIRECTORY_SEPARATOR . Parser::LINK_FILENAME;
        $databaseName = 'dummy.sqlite';
        $databaseFile = $directory . DIRECTORY_SEPARATOR . $databaseName;
        file_put_contents($linkFile, $databaseName);
        file_put_contents($databaseFile, '');

        try {
            $reader = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Reader')
                ->setConstructorArgs([$directory])
                ->setMethods(['isFileReadable'])
                ->getMock();
            $reader->expects(static::any())->method('isFileReadable')->willReturnCallback(function($file) {
                return (strpos($file, '.sqlite') === false);
            });

            $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
            $method = $class->getMethod('getAdapter');
            $method->setAccessible(true);

            $method->invokeArgs($reader, []);
        } finally {
            unlink($linkFile);
            unlink($databaseFile);
        }
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::findBrowser
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testBrowserNotFound()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection OnlyWritesOnParameterInspection */
        $directory = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR .
            'parser' . DIRECTORY_SEPARATOR . Parser::SUB_DIRECTORY;

        $reader = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Reader')
            ->setConstructorArgs([$directory])
            ->setMethods(['findBrowserInKeywordTables', 'findBrowserInDefaultTable'])
            ->getMock();
        $reader->expects(static::any())->method('findBrowserInKeywordTables')->willReturn(null);
        $reader->expects(static::any())->method('findBrowserInDefaultTable')->willReturn(null);

        /** @var Reader $reader */
        $reader->getBrowser(self::USER_AGENT);
    }

    /**
     * @covers ::getBrowserParentId
     */
    public function testFindCalledFromGetBrowserParentId()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Parser\Sqlite\Reader');
        $method = $class->getMethod('getBrowserParentId');
        $method->setAccessible(true);

        $method->invokeArgs($this->reader, [self::USER_AGENT]);
    }

    /**
     * @covers ::getBrowser
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws UnexpectedValueException
     */
    public function testPropertyFilter()
    {
        $propertyFilter = new Disallowed(['Parent', 'browser_name_pattern']);
        $this->reader->setPropertyFilter($propertyFilter);
        $result = $this->reader->getBrowser(self::USER_AGENT);

        static::assertArrayNotHasKey('Parent', $result);
        static::assertArrayNotHasKey('browser_name_pattern', $result);
    }
}
