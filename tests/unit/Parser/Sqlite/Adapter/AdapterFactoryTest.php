<?php
namespace Crossjoin\Browscap\Tests\Unit\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterFactory;

/**
 * Class AdapterFactoryTest
 *
 * @package Crossjoin\Browscap\Test\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterFactory
 */
class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @after
     *
     * @throws InvalidArgumentException
     */
    public function resetAdapterClasses()
    {
        AdapterFactory::setAdapterClasses(AdapterFactory::DEFAULT_CLASSES);
    }

    /**
     * @covers ::getInstance
     * @covers ::getInstanceByClassName
     * @covers ::setAdapterClasses
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testPdo()
    {
        AdapterFactory::setAdapterClasses(['\Crossjoin\Browscap\Parser\Sqlite\Adapter\Pdo']);

        $result = AdapterFactory::getInstance('dummy.sqlite');

        static::assertInstanceOf('\Crossjoin\Browscap\Parser\Sqlite\Adapter\Pdo', $result);
    }

    /**
     * @covers ::getInstance
     * @covers ::getInstanceByClassName
     * @covers ::setAdapterClasses
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testSqlite3()
    {
        AdapterFactory::setAdapterClasses(['\Crossjoin\Browscap\Parser\Sqlite\Adapter\Sqlite3']);

        $result = AdapterFactory::getInstance('dummy.sqlite');

        static::assertInstanceOf('\Crossjoin\Browscap\Parser\Sqlite\Adapter\Sqlite3', $result);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException
     *
     * @covers ::getInstance
     * @covers ::getInstanceByClassName
     * @covers ::setAdapterClasses
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testNone()
    {
        AdapterFactory::setAdapterClasses([]);

        AdapterFactory::getInstance('dummy.sqlite');
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\InvalidArgumentException
     *
     * @covers ::setAdapterClasses
     *
     * @throws InvalidArgumentException
     */
    public function testInvalidAdapterClass()
    {
        AdapterFactory::setAdapterClasses([null]);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\UnexpectedValueException
     * @expectedExceptionCode 1459000690
     *
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testInvalidAdapterClassName()
    {
        AdapterFactory::setAdapterClasses(['UnknownClass']);

        AdapterFactory::getInstance('dummy.sqlite');
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\UnexpectedValueException
     * @expectedExceptionCode 1459000689
     *
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testInvalidAdapterInterface()
    {
        AdapterFactory::setAdapterClasses([self::class]);

        AdapterFactory::getInstance('dummy.sqlite');
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException
     *
     * @covers ::getInstanceByClassName
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function testUnavailableAdapter()
    {
        AdapterFactory::setAdapterClasses(['\Crossjoin\Browscap\Tests\Mock\Parser\Sqlite\Adapter\PdoUnavailable']);

        AdapterFactory::getInstance('dummy.sqlite');
    }
}
