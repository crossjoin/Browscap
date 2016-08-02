<?php
namespace Crossjoin\Browscap\Tests\Unit\Source\Ini;

use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Source\Ini\BrowscapOrg;
use Crossjoin\Browscap\Tests\Mock\Source\GuzzleClient;
use Crossjoin\Browscap\Type;
use Psr\Http\Message\StreamInterface;

/**
 * Class BrowscapOrgTest
 *
 * @package Crossjoin\Browscap\Test\Source\Init
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\Ini\BrowscapOrg
 */
class BrowscapOrgTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataValidType()
    {
        return [
            [Type::LITE, 'http://browscap.org/stream?q=Lite_PHP_BrowscapINI'],
            [Type::STANDARD, 'http://browscap.org/stream?q=PHP_BrowscapINI'],
            [Type::FULL, 'http://browscap.org/stream?q=Full_PHP_BrowscapINI'],
        ];
    }

    /**
     * @param int $type
     * @param string $expectedUri
     *
     * @dataProvider dataValidType
     *
     * @covers ::__construct
     * @covers ::setType
     * @covers ::getType
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::__construct
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::setClient
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::setSourceUri
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::getSourceUri
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws SourceConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testValidType($type, $expectedUri)
    {
        $source = new BrowscapOrg($type);

        static::assertSame($type, $source->getType());
        static::assertSame($expectedUri, $source->getSourceUri());
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\UnexpectedValueException
     *
     * @throws SourceConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testInvalidType()
    {
        new BrowscapOrg(Type::UNKNOWN);
    }

    /**
     * @covers ::getUserAgent
     *
     * @throws SourceConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testUserAgent()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Source\Ini\BrowscapOrg');
        $method = $class->getMethod('getUserAgent');
        $method->setAccessible(true);

        $source = new BrowscapOrg(Type::STANDARD);
        $result = $method->invokeArgs($source, []);

        static::assertNotRegExp('/%[a-zA-Z]+/', $result);
    }

    /**
     * @covers ::getVersion
     *
     * @throws \RuntimeException
     * @throws SourceUnavailableException
     */
    public function testVersion()
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, '6012');
        fseek($fp, 0);
        $stream = \GuzzleHttp\Psr7\stream_for($fp);

        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\BrowscapOrg')
            ->setMethods(['loadContent'])
            ->setConstructorArgs([Type::STANDARD])
            ->getMock();
        $mock->expects(static::once())->method('loadContent')->willReturn($stream);

        /** @var BrowscapOrg $mock */
        static::assertSame(6012, $mock->getVersion());
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1459162266
     *
     * @covers ::getVersion
     *
     * @throws \RuntimeException
     * @throws SourceUnavailableException
     */
    public function testVersionUnreadableStream()
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, '6012');
        fseek($fp, 0);
        /** @var StreamInterface $stream */
        $stream = \GuzzleHttp\Psr7\stream_for($fp);
        $stream ->close();

        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\BrowscapOrg')
            ->setMethods(['loadContent'])
            ->setConstructorArgs([Type::STANDARD])
            ->getMock();
        $mock->expects(static::once())->method('loadContent')->willReturn($stream);

        /** @var BrowscapOrg $mock */
        $mock->getVersion();
    }

    /**
     * @covers ::getReleaseTime
     *
     * @throws \RuntimeException
     * @throws SourceUnavailableException
     */
    public function testReleaseTime()
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, 'Thu, 04 Feb 2016 12:59:23 +0000');
        fseek($fp, 0);
        $stream = \GuzzleHttp\Psr7\stream_for($fp);

        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\BrowscapOrg')
            ->setMethods(['loadContent'])
            ->setConstructorArgs([Type::STANDARD])
            ->getMock();
        $mock->expects(static::once())->method('loadContent')->willReturn($stream);

        /** @var BrowscapOrg $mock */
        static::assertSame(1454590763, $mock->getReleaseTime());
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1459162265
     *
     * @covers ::getReleaseTime
     *
     * @throws \RuntimeException
     * @throws SourceUnavailableException
     */
    public function testReleaseTimeUnreadableStream()
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, 'Thu, 04 Feb 2016 12:59:23 +0000');
        fseek($fp, 0);
        /** @var StreamInterface $stream */
        $stream = \GuzzleHttp\Psr7\stream_for($fp);
        $stream ->close();

        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\BrowscapOrg')
            ->setMethods(['loadContent'])
            ->setConstructorArgs([Type::STANDARD])
            ->getMock();
        $mock->expects(static::once())->method('loadContent')->willReturn($stream);

        /** @var BrowscapOrg $mock */
        $mock->getReleaseTime();
    }

    /**
     * @covers ::getContent
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::getContent
     *
     * @throws \RuntimeException
     * @throws SourceUnavailableException
     */
    public function testData()
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, 'some browscap data');
        fseek($fp, 0);
        $stream = \GuzzleHttp\Psr7\stream_for($fp);

        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\BrowscapOrg')
            ->setMethods(['loadContent'])
            ->setConstructorArgs([Type::STANDARD])
            ->getMock();
        $mock->expects(static::once())->method('loadContent')->willReturn($stream);

        /** @var BrowscapOrg $mock */
        $content = '';
        foreach ($mock->getContent() as $data) {
            $content .= $data;
        }
        static::assertSame('some browscap data', $content);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1459162267
     *
     * @covers ::getContent
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::getContent
     *
     * @throws \RuntimeException
     * @throws SourceUnavailableException
     */
    public function testDataUnreadableStream()
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, 'some browscap data');
        fseek($fp, 0);
        /** @var StreamInterface $stream */
        $stream = \GuzzleHttp\Psr7\stream_for($fp);
        $stream->close();

        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\BrowscapOrg')
            ->setMethods(['loadContent'])
            ->setConstructorArgs([Type::STANDARD])
            ->getMock();
        $mock->expects(static::once())->method('loadContent')->willReturn($stream);

        /** @var BrowscapOrg $mock */
        $content = '';
        foreach ($mock->getContent() as $data) {
            $content .= $data;
        }
    }

    /**
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::getClient
     *
     * @throws SourceConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testClient()
    {
        $source = new BrowscapOrg(Type::STANDARD);
        $client = $source->getClient();

        static::assertInstanceOf('\GuzzleHttp\Client', $client);
    }

    /**
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::loadContent
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws \RuntimeException
     * @throws SourceConditionNotSatisfiedException
     * @throws SourceUnavailableException
     * @throws UnexpectedValueException
     */
    public function testLoadData()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Source\Ini\BrowscapOrg');
        $method = $class->getMethod('setClient');
        $method->setAccessible(true);

        $source = new BrowscapOrg(Type::STANDARD);
        $client = new GuzzleClient(GuzzleClient::MODE_SUCCESS);
        $method->invokeArgs($source, [$client]);

        static::assertSame(0, $source->getVersion());
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1459162269
     *
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::loadContent
     *
     * @throws \RuntimeException
     * @throws SourceConditionNotSatisfiedException
     * @throws SourceUnavailableException
     * @throws UnexpectedValueException
     */
    public function testGuzzleException()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Source\Ini\BrowscapOrg');
        $method = $class->getMethod('setClient');
        $method->setAccessible(true);

        $source = new BrowscapOrg(Type::STANDARD);
        $client = new GuzzleClient(GuzzleClient::MODE_INTERNAL_EXCEPTION);
        $method->invokeArgs($source, [$client]);

        $source->getVersion();
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceUnavailableException
     * @expectedExceptionCode 1459162268
     *
     * @covers \Crossjoin\Browscap\Source\DownloadAbstract::loadContent
     *
     * @throws \RuntimeException
     * @throws SourceConditionNotSatisfiedException
     * @throws SourceUnavailableException
     * @throws UnexpectedValueException
     */
    public function testInvalidDownloadStatus()
    {
        $class = new \ReflectionClass('\Crossjoin\Browscap\Source\Ini\BrowscapOrg');
        $method = $class->getMethod('setClient');
        $method->setAccessible(true);

        $source = new BrowscapOrg(Type::STANDARD);
        $client = new GuzzleClient(GuzzleClient::MODE_INVALID_STATUS_CODE);
        $method->invokeArgs($source, [$client]);

        $source->getVersion();
    }
}
