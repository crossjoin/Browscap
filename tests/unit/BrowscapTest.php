<?php
namespace Crossjoin\Browscap\Tests\Unit;

use Crossjoin\Browscap\Browscap;
use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Formatter\Optimized;
use Crossjoin\Browscap\Parser\ParserInterface;
use Crossjoin\Browscap\Parser\Sqlite\Parser;
use Crossjoin\Browscap\Source\Ini\File;

/**
 * Class BrowscapTest
 *
 * @package Crossjoin\Browscap\Test\Unit
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Browscap
 */
class BrowscapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getParser
     *
     * @throws ParserConfigurationException
     */
    public function testDefaultParser()
    {
        $browscap = new Browscap();
        $parser = $browscap->getParser();

        static::assertInstanceOf('\Crossjoin\Browscap\Parser\Sqlite\Parser', $parser);
    }

    /**
     * @covers ::setParser
     *
     * @throws ParserConfigurationException
     */
    public function testCustomParser()
    {
        /** @var ParserInterface $mock */
        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Parser')->getMock();

        $browscap = new Browscap();
        $browscap->setParser($mock);
        $parser = $browscap->getParser();

        static::assertSame($mock, $parser);
    }

    /**
     * @covers ::getFormatter
     */
    public function testDefaultFormatter()
    {
        $browscap = new Browscap();
        $formatter = $browscap->getFormatter();

        static::assertInstanceOf('\Crossjoin\Browscap\Formatter\PhpGetBrowser', $formatter);
    }

    /**
     * @covers ::setFormatter
     */
    public function testCustomFormatter()
    {
        $customFormatter = new Optimized();

        $browscap = new Browscap();
        $browscap->setFormatter($customFormatter);
        $formatter = $browscap->getFormatter();

        static::assertSame($customFormatter, $formatter);
    }

    /**
     * @covers ::getAutoUpdateProbability
     */
    public function testDefaultAutoUpdateProbability()
    {
        $browscap = new Browscap();
        $probability = $browscap->getAutoUpdateProbability();

        static::assertSame(0, $probability);
    }

    /**
     * @covers ::setAutoUpdateProbability
     */
    public function testCustomAutoUpdateProbability()
    {
        $browscap = new Browscap();
        $browscap->setAutoUpdateProbability(100);
        $probability = $browscap->getAutoUpdateProbability();

        static::assertSame(100, $probability);
    }

    /**
     * @covers ::__invoke
     * @covers ::getBrowser
     * @covers ::autoUpdate
     *
     * @throws ParserConfigurationException
     */
    public function testGetBrowserViaInvoke()
    {
        $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'parser' . DIRECTORY_SEPARATOR;
        $parser = new Parser($directory);

        $browscap = new Browscap();
        $browscap->setParser($parser);

        $result = $browscap('an unknown user agent');

        static::assertInternalType('object', $result);
    }

    /**
     * @covers ::getBrowser
     * @covers ::autoUpdate
     * @covers ::update
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testGetBrowserWithAutoUpdate()
    {
        $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'parser' . DIRECTORY_SEPARATOR;

        $iniFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'source' .
            DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';

        $parser = new Parser($directory);
        $source = new File($iniFile);
        $parser->setSource($source);

        $browscap = new Browscap();
        $browscap->setParser($parser);
        $browscap->setAutoUpdateProbability(100);

        $result = $browscap->getBrowser();

        static::assertInternalType('object', $result);
        static::assertSame($source->getVersion(), $parser->getReader()->getVersion());
    }

    /**
     * @covers ::update
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testForcedUpdate()
    {
        $iniFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'source' .
            DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';
        $source = new File($iniFile);

        $browscap = new Browscap();
        $browscap->getParser()->setSource($source);
        $browscap->update(true);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::update
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function testUpdateLoop()
    {
        $reader = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Reader')
            ->disableOriginalConstructor()
            ->setMethods(['isUpdateRequired'])
            ->getMock();
        $reader->expects(static::any())->method('isUpdateRequired')->willReturn(true);

        $parser = $this->getMockBuilder('\Crossjoin\Browscap\Parser\Sqlite\Parser')
            ->disableOriginalConstructor()
            ->setMethods(['getReader'])
            ->getMock();
        $parser->expects(static::any())->method('getReader')->willReturn($reader);

        $iniFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'source' .
            DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';
        $source = new File($iniFile);
        /** @var \Crossjoin\Browscap\Parser\Sqlite\Parser $parser */
        $parser->setSource($source);

        $browscap = new Browscap();
        $browscap->setParser($parser);
        $browscap->update(true);
    }
}
