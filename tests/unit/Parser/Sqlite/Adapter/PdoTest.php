<?php
namespace Crossjoin\Browscap\Tests\Unit\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\Pdo;
use Crossjoin\Browscap\Tests\Mock\Parser\Sqlite\Adapter\PdoUnavailable;

/**
 * Class PdoTest
 *
 * @package Crossjoin\Browscap\Test\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Parser\Sqlite\Adapter\Pdo
 */
class PdoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $tempFile;

    /**
     * @var Pdo
     */
    protected $adapterInstance;

    public function setUp()
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'cb-');
        $this->adapterInstance = new Pdo($this->tempFile);
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($this->adapterInstance);
        @unlink($this->tempFile);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConfigurationException
     *
     * @covers \Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterAbstract::__construct
     * @covers \Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterAbstract::setFileName
     * @covers \Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterAbstract::getFileName
     * @covers ::__construct
     * @covers ::checkConditions
     * @covers ::getConnection
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testInvalidFileName()
    {
        $adapter = new Pdo('..');
        $adapter->query('SELECT 1');
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException
     *
     * @covers ::__construct
     * @covers ::checkConditions
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function testConditionsNotSatisfied()
    {
        new PdoUnavailable('');
    }

    /**
     * @covers ::exec
     * @covers ::getConnection
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testValidExec()
    {
        $this->adapterInstance->exec('CREATE TABLE IF NOT EXISTS dummy (foo TEXT)');
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::exec
     * @covers ::getConnection
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testInvalidExec()
    {
        $this->adapterInstance->exec('CREATE CHAIR dummy (foo TEXT)');
    }

    /**
     * @covers ::query
     * @covers ::exec
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testValidQueryOne()
    {
        $this->adapterInstance->exec('CREATE TABLE IF NOT EXISTS dummy (foo TEXT)');
        $this->adapterInstance->exec('INSERT INTO dummy VALUES (123)');
        $result = $this->adapterInstance->query('SELECT COUNT(*) AS cnt FROM dummy');
        $count = 0;
        if (array_key_exists(0, $result) && array_key_exists('cnt', $result[0])) {
            $count = (int)$result[0]['cnt'];
        }

        static::assertGreaterThan(0, (int)$count);
    }

    /**
     * @covers ::query
     * @covers ::exec
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testValidQueryMultiple()
    {
        $this->adapterInstance->exec('CREATE TABLE IF NOT EXISTS dummy (foo TEXT)');
        $this->adapterInstance->exec('INSERT INTO dummy VALUES (123), (234), (345)');
        $result = $this->adapterInstance->query('SELECT foo FROM dummy');
        $value = 0;
        if (array_key_exists(1, $result) && array_key_exists('foo', $result[1])) {
            $value = (int)$result[1]['foo'];
        }

        static::assertSame(234, $value);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::query
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testInvalidQuery()
    {
        $this->adapterInstance->query('SELECT * FROM doesnotexist');
    }

    /**
     * @covers ::prepare
     * @covers ::exec
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testValidPrepare()
    {
        $this->adapterInstance->exec('CREATE TABLE IF NOT EXISTS dummy (foo TEXT)');
        $result = $this->adapterInstance->prepare('SELECT COUNT(*) AS cnt FROM dummy WHERE foo = :value');

        static::assertInstanceOf('\Crossjoin\Browscap\Parser\Sqlite\Adapter\PdoPreparedStatement', $result);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::prepare
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testInvalidPrepare()
    {
        $this->adapterInstance->prepare('SELECT * FROM doesnotexist WHERE field = :value');
    }

    /**
     * @covers ::beginTransaction
     * @covers ::commitTransaction
     * @covers ::getConnection
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testValidTransaction()
    {
        $this->adapterInstance->beginTransaction();
        $this->adapterInstance->commitTransaction();
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::beginTransaction
     * @covers ::getConnection
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testInvalidTransactionBegin()
    {
        $this->adapterInstance->beginTransaction();
        $this->adapterInstance->beginTransaction();
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\ParserRuntimeException
     *
     * @covers ::commitTransaction
     * @covers ::getConnection
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testInvalidTransactionCommit()
    {
        $this->adapterInstance->commitTransaction();
    }

    /**
     * @covers ::__destruct
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testDestructor()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cb-');
        $adapter = new Pdo($tempFile);
        $adapter->query('SELECT 1');
        unset($adapter);
        @unlink($tempFile);
    }
}
