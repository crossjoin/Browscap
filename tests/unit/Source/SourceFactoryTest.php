<?php
namespace Crossjoin\Browscap\Tests\Unit\Source;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Source\SourceFactory;

/**
 * Class IniSourceFactoryTest
 *
 * @package Crossjoin\Browscap\Test\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\SourceFactory
 */
class SourceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @after
     *
     * @throws InvalidArgumentException
     */
    public function resetSourceClasses()
    {
        SourceFactory::setSourceClasses(SourceFactory::DEFAULT_CLASSES);
    }

    /**
     * @covers ::getInstance
     * @covers ::getInstanceByClassName
     *
     * @throws UnexpectedValueException
     */
    public function testInstance()
    {
        $result = SourceFactory::getInstance();
        static::assertInstanceOf('\Crossjoin\Browscap\Source\SourceInterface', $result);
    }

    /**
     * @covers ::getInstance
     * @covers ::setSourceClasses
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function testEmptySources()
    {
        SourceFactory::setSourceClasses([]);
        $result = SourceFactory::getInstance();
        static::assertInstanceOf('\Crossjoin\Browscap\Source\None', $result);
    }

    /**
     * @covers ::getInstance
     * @covers ::setSourceClasses
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function testUnavailableSources()
    {
        SourceFactory::setSourceClasses(['\Crossjoin\Browscap\Tests\Mock\Source\Ini\Unavailable']);
        $result = SourceFactory::getInstance();
        static::assertInstanceOf('\Crossjoin\Browscap\Source\None', $result);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\InvalidArgumentException
     *
     * @covers ::setSourceClasses
     *
     * @throws InvalidArgumentException
     */
    public function testInvalidSourceClassNameType()
    {
        SourceFactory::setSourceClasses([null]);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\UnexpectedValueException
     * @expectedExceptionCode 1459069588
     *
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function testNonExistentSourceClassName()
    {
        SourceFactory::setSourceClasses(['\Does\Not\Exists']);
        SourceFactory::getInstance();
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\UnexpectedValueException
     * @expectedExceptionCode 1459069587
     *
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function testSourceClassNameWithoutInterface()
    {
        SourceFactory::setSourceClasses([self::class]);
        SourceFactory::getInstance();
    }
}
